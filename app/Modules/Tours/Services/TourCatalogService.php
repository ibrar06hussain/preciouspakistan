<?php

namespace App\Modules\Tours\Services;

use App\Models\User;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use App\Modules\Rbac\Services\AuditLogger;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourDeparture;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class TourCatalogService
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
        private readonly DestinationHierarchyService $geoService,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Create or update a tour package with destinations, chronological itinerary, overnight stays, and departures.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function saveTour(?User $actor, ?Tour $existing, array $data): Tour
    {
        if ($actor !== null) {
            $requiredPermission = $existing === null ? 'tours.create' : 'tours.update';
            if (! $actor->can($requiredPermission)) {
                throw new AuthorizationException("You do not have permission [{$requiredPermission}].");
            }
        }

        $title = $this->sanitizer->sanitizePlainText($data['title'] ?? $existing?->title ?? '', 'tour.title', 191);
        if ($title === '') {
            throw new InvalidArgumentException('Tour title is required.');
        }

        $slug = Str::slug((string) ($data['slug'] ?? $existing?->slug ?? $title));
        if ($slug === '') {
            throw new InvalidArgumentException('A valid tour slug is required.');
        }

        $category = (string) ($data['category'] ?? $existing?->category ?? 'sightseeing');
        if (! array_key_exists($category, Tour::CATEGORIES)) {
            throw new InvalidArgumentException("Invalid tour category [{$category}].");
        }

        $difficulty = (string) ($data['difficulty'] ?? $existing?->difficulty ?? 'moderate');
        if (! array_key_exists($difficulty, Tour::DIFFICULTIES)) {
            throw new InvalidArgumentException("Invalid tour difficulty [{$difficulty}].");
        }

        $durationDays = (int) ($data['duration_days'] ?? $existing?->duration_days ?? 1);
        $durationNights = (int) ($data['duration_nights'] ?? $existing?->duration_nights ?? max(0, $durationDays - 1));
        if ($durationDays < 1 || $durationDays > 60) {
            throw new InvalidArgumentException('Tour duration must be between 1 and 60 days.');
        }
        if ($durationNights < 0 || $durationNights > $durationDays) {
            throw new InvalidArgumentException('Duration nights must be between 0 and duration days.');
        }

        $minGuests = (int) ($data['min_guests'] ?? $existing?->min_guests ?? 1);
        $maxGuests = (int) ($data['max_guests'] ?? $existing?->max_guests ?? 12);
        if ($minGuests < 1 || $maxGuests < $minGuests || $maxGuests > 100) {
            throw new InvalidArgumentException('Passenger caps are invalid: min_guests must be >= 1 and <= max_guests (max 100).');
        }

        $leadDays = max(0, (int) ($data['booking_lead_days'] ?? $existing?->booking_lead_days ?? 2));
        $windowDays = max($leadDays + 1, (int) ($data['booking_window_days'] ?? $existing?->booking_window_days ?? 365));

        $pricingModel = (string) ($data['pricing_model'] ?? $existing?->pricing_model ?? Tour::PRICING_CUSTOM_QUOTE);
        if (! array_key_exists($pricingModel, Tour::PRICING_MODELS)) {
            throw new InvalidArgumentException("Invalid pricing model [{$pricingModel}].");
        }

        [$pricePkr, $priceUsd] = $this->validatePricing(
            $pricingModel,
            $data['base_price_pkr'] ?? null,
            $data['base_price_usd'] ?? null
        );

        $descriptionHtml = filled($data['description_html'] ?? null)
            ? $this->sanitizer->sanitizeRichHtml((string) $data['description_html'], 'tour.description_html')
            : null;
        $cancellationHtml = filled($data['cancellation_policy_html'] ?? null)
            ? $this->sanitizer->sanitizeRichHtml((string) $data['cancellation_policy_html'], 'tour.cancellation_policy_html')
            : null;

        $destinationIds = array_key_exists('destination_ids', $data)
            ? array_values(array_unique(array_map('intval', (array) $data['destination_ids'])))
            : null;

        if ($destinationIds !== null && $destinationIds !== []) {
            $validCount = Destination::query()->whereIn('id', $destinationIds)->count();
            if ($validCount !== count($destinationIds)) {
                throw new InvalidArgumentException('One or more selected destinations do not exist.');
            }
        }

        $itineraryDays = array_key_exists('itinerary_days', $data)
            ? $this->validateItineraryDays((array) $data['itinerary_days'], $durationDays)
            : null;

        $stays = array_key_exists('stays', $data)
            ? $this->validateStays((array) $data['stays'], $durationDays, $durationNights)
            : null;

        $departures = array_key_exists('departures', $data)
            ? $this->validateDepartures((array) $data['departures'], $minGuests)
            : null;

        $code = trim((string) ($data['code'] ?? $existing?->code ?? ''));
        if ($code === '') {
            $code = 'PP-'.strtoupper(Str::random(6));
        }

        return DB::transaction(function () use (
            $actor,
            $existing,
            $data,
            $code,
            $title,
            $slug,
            $category,
            $difficulty,
            $durationDays,
            $durationNights,
            $minGuests,
            $maxGuests,
            $leadDays,
            $windowDays,
            $pricingModel,
            $pricePkr,
            $priceUsd,
            $descriptionHtml,
            $cancellationHtml,
            $destinationIds,
            $itineraryDays,
            $stays,
            $departures,
        ): Tour {
            $oldSlug = $existing?->slug;
            $wasPublished = $existing?->isPublished() ?? false;

            $tour = $existing ?? new Tour;
            $tour->fill([
                'code' => strtoupper(mb_substr($code, 0, 40)),
                'title' => $title,
                'slug' => $slug,
                'category' => $category,
                'status' => $existing?->status ?? Tour::STATUS_DRAFT,
                'duration_days' => $durationDays,
                'duration_nights' => $durationNights,
                'min_guests' => $minGuests,
                'max_guests' => $maxGuests,
                'booking_lead_days' => $leadDays,
                'booking_window_days' => $windowDays,
                'difficulty' => $difficulty,
                'languages' => $this->sanitizeStringList($data['languages'] ?? $existing?->languages ?? ['English', 'Urdu']),
                'pickup_location' => $this->sanitizer->sanitizePlainText($data['pickup_location'] ?? null, 'tour.pickup_location', 180) ?: null,
                'dropoff_location' => $this->sanitizer->sanitizePlainText($data['dropoff_location'] ?? null, 'tour.dropoff_location', 180) ?: null,
                'summary' => $this->sanitizer->sanitizePlainText($data['summary'] ?? null, 'tour.summary', 1000) ?: null,
                'description_html' => $descriptionHtml,
                'highlights' => $this->sanitizeStringList($data['highlights'] ?? $existing?->highlights ?? []),
                'inclusions' => $this->sanitizeStringList($data['inclusions'] ?? $existing?->inclusions ?? []),
                'exclusions' => $this->sanitizeStringList($data['exclusions'] ?? $existing?->exclusions ?? []),
                'cancellation_policy_html' => $cancellationHtml,
                'faqs' => $this->sanitizeFaqs($data['faqs'] ?? $existing?->faqs ?? []),
                'hero_image_url' => $this->sanitizer->sanitizeUrl($data['hero_image_url'] ?? null, 'tour.hero_image_url') ?: null,
                'gallery' => $this->sanitizeGallery($data['gallery'] ?? $existing?->gallery ?? []),
                'pricing_model' => $pricingModel,
                'base_price_pkr' => $pricePkr,
                'base_price_usd' => $priceUsd,
                'includes_base_accommodation' => (bool) ($data['includes_base_accommodation'] ?? $existing?->includes_base_accommodation ?? false),
                'requires_company_vehicle' => (bool) ($data['requires_company_vehicle'] ?? $existing?->requires_company_vehicle ?? true),
                'supports_hotel_selection' => (bool) ($data['supports_hotel_selection'] ?? $existing?->supports_hotel_selection ?? true),
                'supports_nomad_addon' => (bool) ($data['supports_nomad_addon'] ?? $existing?->supports_nomad_addon ?? false),
                'is_featured' => (bool) ($data['is_featured'] ?? $existing?->is_featured ?? false),
                'is_sample_content' => (bool) ($data['is_sample_content'] ?? $existing?->is_sample_content ?? true),
                'seo_title' => $this->sanitizer->sanitizePlainText($data['seo_title'] ?? null, 'tour.seo_title', 180) ?: null,
                'seo_description' => $this->sanitizer->sanitizePlainText($data['seo_description'] ?? null, 'tour.seo_description', 320) ?: null,
                'canonical_url' => $this->sanitizer->sanitizeUrl($data['canonical_url'] ?? null, 'tour.canonical_url') ?: null,
                'og_image_url' => $this->sanitizer->sanitizeUrl($data['og_image_url'] ?? null, 'tour.og_image_url') ?: null,
                'noindex' => (bool) ($data['noindex'] ?? $existing?->noindex ?? false),
                'created_by' => $existing?->created_by ?? $actor?->id,
                'updated_by' => $actor?->id ?? $existing?->updated_by,
            ])->save();

            if ($destinationIds !== null) {
                $syncMap = [];
                foreach ($destinationIds as $idx => $destId) {
                    $syncMap[$destId] = [
                        'sort_order' => $idx,
                        'is_primary' => $idx === 0,
                    ];
                }
                $tour->destinations()->sync($syncMap);
            }

            if ($itineraryDays !== null) {
                $tour->itineraryDays()->delete();
                foreach ($itineraryDays as $day) {
                    $tour->itineraryDays()->create($day);
                }
            }

            if ($stays !== null) {
                $tour->stays()->delete();
                foreach ($stays as $stay) {
                    $tour->stays()->create($stay);
                }
            }

            if ($departures !== null) {
                $tour->departures()->delete();
                foreach ($departures as $dep) {
                    $tour->departures()->create($dep);
                }
            }

            if ($wasPublished && $oldSlug !== null && $oldSlug !== $tour->slug) {
                $this->geoService->registerRedirect($actor, '/tours/'.$oldSlug, '/tours/'.$tour->slug, 'tour_slug_changed');
            }

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: $existing === null ? 'tour.created' : 'tour.updated',
                    summary: "Saved tour [{$tour->code}] {$tour->title}",
                    auditable: $tour,
                    beforeState: $oldSlug ? ['slug' => $oldSlug] : null,
                    afterState: ['slug' => $tour->slug, 'status' => $tour->status, 'pricing_model' => $tour->pricing_model],
                );
            }

            return $tour->fresh(['destinations', 'itineraryDays', 'stays', 'departures']);
        });
    }

    /**
     * Publish a tour only when ALL mandatory essentials are valid.
     *
     * @throws AuthorizationException
     */
    public function publishTour(?User $actor, Tour $tour): Tour
    {
        if ($actor !== null && ! $actor->can('tours.publish')) {
            throw new AuthorizationException('You do not have permission to publish tours.');
        }

        $errors = $this->validatePublishReadiness($tour);
        if ($errors !== []) {
            throw new InvalidArgumentException('Tour cannot be published until required essentials are completed: '.implode(' ', $errors));
        }

        return DB::transaction(function () use ($actor, $tour): Tour {
            $locked = Tour::query()->lockForUpdate()->findOrFail($tour->id);
            $previousStatus = $locked->status;

            $locked->forceFill([
                'status' => Tour::STATUS_PUBLISHED,
                'published_at' => $locked->published_at ?? now(),
                'updated_by' => $actor?->id ?? $locked->updated_by,
            ])->save();

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: 'tour.published',
                    summary: "Published tour [{$locked->code}] {$locked->title}",
                    auditable: $locked,
                    beforeState: ['status' => $previousStatus],
                    afterState: ['status' => Tour::STATUS_PUBLISHED],
                );
            }

            return $locked->fresh();
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function moveToDraft(User $actor, Tour $tour): Tour
    {
        if (! $actor->can('tours.update')) {
            throw new AuthorizationException('You do not have permission to move tours to draft.');
        }

        $previous = $tour->status;
        $tour->forceFill(['status' => Tour::STATUS_DRAFT, 'updated_by' => $actor->id])->save();

        $this->audit->record(
            actor: $actor,
            event: 'tour.moved_to_draft',
            summary: "Moved tour [{$tour->code}] to draft",
            auditable: $tour,
            beforeState: ['status' => $previous],
            afterState: ['status' => Tour::STATUS_DRAFT],
        );

        return $tour->fresh();
    }

    /**
     * @throws AuthorizationException
     */
    public function archiveTour(User $actor, Tour $tour): Tour
    {
        if (! $actor->can('tours.publish')) {
            throw new AuthorizationException('You do not have permission to archive tours.');
        }

        $previous = $tour->status;
        $tour->forceFill(['status' => Tour::STATUS_ARCHIVED, 'updated_by' => $actor->id])->save();

        $this->audit->record(
            actor: $actor,
            event: 'tour.archived',
            summary: "Archived tour [{$tour->code}] {$tour->title}",
            auditable: $tour,
            beforeState: ['status' => $previous],
            afterState: ['status' => Tour::STATUS_ARCHIVED],
        );

        return $tour->fresh();
    }

    /**
     * @return list<string>
     */
    public function validatePublishReadiness(Tour $tour): array
    {
        $tour->loadMissing(['destinations', 'itineraryDays']);
        $errors = [];

        if (blank($tour->title) || blank($tour->summary) || blank($tour->description_html)) {
            $errors[] = 'Title, summary, and full rich description are required.';
        }

        if ($tour->destinations->isEmpty()) {
            $errors[] = 'At least one destination must be attached.';
        }

        $dayNumbers = $tour->itineraryDays->pluck('day_number')->sort()->values()->all();
        $expectedDays = range(1, max(1, $tour->duration_days));
        if ($dayNumbers !== $expectedDays) {
            $errors[] = "Chronological itinerary days 1..{$tour->duration_days} are required without gaps.";
        }

        if ($tour->pricing_model !== Tour::PRICING_CUSTOM_QUOTE) {
            $hasPkr = $tour->base_price_pkr !== null && bccomp((string) $tour->base_price_pkr, '0.00', 2) > 0;
            $hasUsd = $tour->base_price_usd !== null && bccomp((string) $tour->base_price_usd, '0.00', 2) > 0;
            if (! ($hasPkr || $hasUsd)) {
                $errors[] = 'Priced tours must have a positive PKR or USD base rate (or use custom_quote).';
            }
        }

        return $errors;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function validatePricing(string $pricingModel, mixed $pkr, mixed $usd): array
    {
        if ($pricingModel === Tour::PRICING_CUSTOM_QUOTE) {
            return [null, null];
        }

        $cleanPkr = $this->normalizePositiveMoney($pkr, 'base_price_pkr');
        $cleanUsd = $this->normalizePositiveMoney($usd, 'base_price_usd');

        return [$cleanPkr, $cleanUsd];
    }

    private function normalizePositiveMoney(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Field [{$field}] must be a valid decimal amount.");
        }

        $formatted = number_format((float) $value, 2, '.', '');
        if (bccomp($formatted, '0.00', 2) <= 0) {
            throw new InvalidArgumentException("Field [{$field}] must be greater than zero (use custom_quote for Request Quote tours).");
        }

        return $formatted;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateItineraryDays(array $rows, int $durationDays): array
    {
        $seenDays = [];
        $sanitized = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $dayNumber = (int) ($row['day_number'] ?? 0);
            if ($dayNumber < 1 || $dayNumber > $durationDays) {
                throw new InvalidArgumentException("Itinerary day number [{$dayNumber}] must be between 1 and {$durationDays}.");
            }

            if (in_array($dayNumber, $seenDays, true)) {
                throw new InvalidArgumentException("Duplicate itinerary day [{$dayNumber}] is not allowed.");
            }
            $seenDays[] = $dayNumber;

            $title = $this->sanitizer->sanitizePlainText($row['title'] ?? null, "itinerary.day_{$dayNumber}.title", 191);
            if ($title === '') {
                throw new InvalidArgumentException("Itinerary day {$dayNumber} must have a title.");
            }

            $sanitized[] = [
                'day_number' => $dayNumber,
                'title' => $title,
                'destination_id' => filled($row['destination_id'] ?? null) ? (int) $row['destination_id'] : null,
                'description_html' => filled($row['description_html'] ?? null)
                    ? $this->sanitizer->sanitizeRichHtml((string) $row['description_html'], "itinerary.day_{$dayNumber}.description_html")
                    : null,
                'meals_included' => $this->sanitizer->sanitizePlainText($row['meals_included'] ?? null, 'itinerary.meals', 120) ?: null,
                'altitude_meters' => filled($row['altitude_meters'] ?? null) ? max(0, (int) $row['altitude_meters']) : null,
                'travel_distance_km' => filled($row['travel_distance_km'] ?? null) ? max(0, (int) $row['travel_distance_km']) : null,
            ];
        }

        usort($sanitized, fn (array $a, array $b): int => $a['day_number'] <=> $b['day_number']);

        // Enforce strict chronological sequence 1..N when days are provided.
        foreach ($sanitized as $index => $day) {
            if ($day['day_number'] !== $index + 1) {
                throw new InvalidArgumentException('Itinerary days must be strictly chronological starting at Day 1 with no gaps.');
            }
        }

        return $sanitized;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateStays(array $rows, int $durationDays, int $durationNights): array
    {
        $sanitized = [];
        $totalNights = 0;

        foreach (array_values($rows) as $idx => $row) {
            if (! is_array($row)) {
                continue;
            }

            $destinationId = (int) ($row['destination_id'] ?? 0);
            if (! Destination::query()->whereKey($destinationId)->exists()) {
                throw new InvalidArgumentException('Each overnight tour stay must reference an existing destination.');
            }

            $checkInDay = (int) ($row['check_in_day'] ?? 0);
            $nights = (int) ($row['nights_count'] ?? 0);

            if ($checkInDay < 1 || $nights < 1 || ($checkInDay + $nights - 1) > $durationDays) {
                throw new InvalidArgumentException('Overnight stay check-in day and nights count exceed tour duration.');
            }

            $totalNights += $nights;

            $sanitized[] = [
                'destination_id' => $destinationId,
                'stay_order' => (int) ($row['stay_order'] ?? ($idx + 1)),
                'check_in_day' => $checkInDay,
                'nights_count' => $nights,
                'notes' => $this->sanitizer->sanitizePlainText($row['notes'] ?? null, 'stay.notes', 255) ?: null,
            ];
        }

        if ($totalNights > $durationNights) {
            throw new InvalidArgumentException("Total overnight stay nights ({$totalNights}) cannot exceed tour duration nights ({$durationNights}).");
        }

        return $sanitized;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateDepartures(array $rows, int $minGuests): array
    {
        $sanitized = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $startDate = CarbonImmutable::parse((string) ($row['start_date'] ?? ''));
            $endDate = CarbonImmutable::parse((string) ($row['end_date'] ?? ''));

            if ($endDate->lt($startDate)) {
                throw new InvalidArgumentException('Departure end date cannot be earlier than start date.');
            }

            $capacity = (int) ($row['capacity'] ?? 0);
            $reserved = max(0, (int) ($row['reserved_seats'] ?? 0));

            if ($capacity < $minGuests || $reserved > $capacity) {
                throw new InvalidArgumentException('Departure capacity must be >= tour min_guests and >= reserved seats.');
            }

            $status = (string) ($row['status'] ?? TourDeparture::STATUS_OPEN);
            if (! in_array($status, [TourDeparture::STATUS_OPEN, TourDeparture::STATUS_FULL, TourDeparture::STATUS_CLOSED, TourDeparture::STATUS_CANCELLED], true)) {
                $status = TourDeparture::STATUS_OPEN;
            }

            $depCode = trim((string) ($row['departure_code'] ?? ''));
            if ($depCode === '') {
                $depCode = 'DEP-'.$startDate->format('Ymd').'-'.strtoupper(Str::random(4));
            }

            $sanitized[] = [
                'departure_code' => strtoupper(mb_substr($depCode, 0, 64)),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'capacity' => $capacity,
                'reserved_seats' => $reserved,
                'price_override_pkr' => $this->normalizePositiveMoney($row['price_override_pkr'] ?? null, 'departure.price_override_pkr'),
                'price_override_usd' => $this->normalizePositiveMoney($row['price_override_usd'] ?? null, 'departure.price_override_usd'),
                'status' => $status,
                'notes' => $this->sanitizer->sanitizePlainText($row['notes'] ?? null, 'departure.notes', 255) ?: null,
            ];
        }

        return $sanitized;
    }

    /**
     * @return list<string>
     */
    private function sanitizeStringList(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, 30) as $item) {
            $val = is_array($item) ? (string) ($item['text'] ?? $item['value'] ?? '') : (string) $item;
            $clean = $this->sanitizer->sanitizePlainText($val, 'tour.list_item', 240);
            if ($clean !== '') {
                $out[] = $clean;
            }
        }

        return $out;
    }

    /**
     * @return list<array{question: string, answer_html: string}>
     */
    private function sanitizeFaqs(mixed $faqs): array
    {
        if (! is_array($faqs)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($faqs), 0, 20) as $faq) {
            if (! is_array($faq)) {
                continue;
            }
            $question = $this->sanitizer->sanitizePlainText($faq['question'] ?? null, 'tour.faq.question', 240);
            if ($question === '') {
                continue;
            }
            $out[] = [
                'question' => $question,
                'answer_html' => $this->sanitizer->sanitizeRichHtml((string) ($faq['answer_html'] ?? ''), 'tour.faq.answer_html'),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{url: string, alt: string, caption: string}>
     */
    private function sanitizeGallery(mixed $gallery): array
    {
        if (! is_array($gallery)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($gallery), 0, 20) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $url = $this->sanitizer->sanitizeUrl($item['url'] ?? null, 'tour.gallery.url');
            if ($url === '') {
                continue;
            }
            $out[] = [
                'url' => $url,
                'alt' => $this->sanitizer->sanitizePlainText($item['alt'] ?? null, 'tour.gallery.alt', 180),
                'caption' => $this->sanitizer->sanitizePlainText($item['caption'] ?? null, 'tour.gallery.caption', 220),
            ];
        }

        return $out;
    }
}
