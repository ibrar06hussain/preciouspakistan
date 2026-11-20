<?php

namespace App\Modules\Hotels\Services;

use App\Models\User;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelRoomRate;
use App\Modules\Hotels\Models\HotelRoomType;
use App\Modules\Hotels\Models\TourStayHotel;
use App\Modules\Rbac\Services\AuditLogger;
use App\Modules\Tours\Models\TourStay;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class HotelCatalogService
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Create or update a company-managed tour-only hotel with room types and seasonal nightly rates.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function saveHotel(?User $actor, ?Hotel $existing, array $data): Hotel
    {
        if ($actor !== null && ! $actor->can('hotels.manage')) {
            throw new AuthorizationException('You do not have permission to manage tour-only hotels.');
        }

        $destinationId = (int) ($data['destination_id'] ?? $existing?->destination_id);
        $destination = Destination::query()->with(['region', 'district'])->find($destinationId);

        if ($destination === null) {
            throw new InvalidArgumentException('Hotel must be associated with an existing Pakistan destination.');
        }

        $name = $this->sanitizer->sanitizePlainText($data['name'] ?? $existing?->name ?? '', 'hotel.name', 180);
        if ($name === '') {
            throw new InvalidArgumentException('Hotel name is required.');
        }

        $slug = Str::slug((string) ($data['slug'] ?? $existing?->slug ?? $name));
        if ($slug === '') {
            throw new InvalidArgumentException('A valid hotel slug is required.');
        }

        $status = (string) ($data['status'] ?? $existing?->status ?? Hotel::STATUS_ACTIVE);
        if (! in_array($status, [Hotel::STATUS_DRAFT, Hotel::STATUS_ACTIVE, Hotel::STATUS_INACTIVE], true)) {
            $status = Hotel::STATUS_ACTIVE;
        }

        $shortDescHtml = filled($data['short_description_html'] ?? null)
            ? $this->sanitizer->sanitizeRichHtml((string) $data['short_description_html'], 'hotel.short_description_html')
            : null;

        $roomTypesPayload = array_key_exists('room_types', $data)
            ? $this->validateRoomTypes((array) $data['room_types'])
            : null;

        return DB::transaction(function () use ($actor, $existing, $destination, $data, $name, $slug, $status, $shortDescHtml, $roomTypesPayload): Hotel {
            $hotel = $existing ?? new Hotel;
            $hotel->fill([
                'name' => $name,
                'slug' => $slug,
                'region_id' => $destination->region_id,
                'district_id' => $destination->district_id,
                'destination_id' => $destination->id,
                'address' => $this->sanitizer->sanitizePlainText($data['address'] ?? null, 'hotel.address', 255) ?: null,
                'latitude' => filled($data['latitude'] ?? null) ? round((float) $data['latitude'], 7) : $existing?->latitude,
                'longitude' => filled($data['longitude'] ?? null) ? round((float) $data['longitude'], 7) : $existing?->longitude,
                'short_description_html' => $shortDescHtml,
                'main_photo_url' => $this->sanitizer->sanitizeUrl($data['main_photo_url'] ?? null, 'hotel.main_photo_url') ?: null,
                'gallery' => $this->sanitizeGallery($data['gallery'] ?? $existing?->gallery ?? []),
                'public_contact_phone' => $this->sanitizer->sanitizePlainText($data['public_contact_phone'] ?? null, 'hotel.phone', 40) ?: null,
                'public_contact_email' => $this->sanitizer->sanitizePlainText($data['public_contact_email'] ?? null, 'hotel.email', 191) ?: null,
                'status' => $status,
                'is_sample_content' => (bool) ($data['is_sample_content'] ?? $existing?->is_sample_content ?? true),
                'seo_title' => $this->sanitizer->sanitizePlainText($data['seo_title'] ?? null, 'hotel.seo_title', 180) ?: null,
                'seo_description' => $this->sanitizer->sanitizePlainText($data['seo_description'] ?? null, 'hotel.seo_description', 320) ?: null,
                'canonical_url' => $this->sanitizer->sanitizeUrl($data['canonical_url'] ?? null, 'hotel.canonical_url') ?: null,
                'noindex' => (bool) ($data['noindex'] ?? $existing?->noindex ?? false),
                'created_by' => $existing?->created_by ?? $actor?->id,
                'updated_by' => $actor?->id ?? $existing?->updated_by,
            ])->save();

            if ($roomTypesPayload !== null) {
                $keptRoomIds = [];
                foreach ($roomTypesPayload as $rtData) {
                    $rates = $rtData['rates'];
                    unset($rtData['rates']);

                    $roomType = $hotel->roomTypes()->updateOrCreate(
                        ['code' => $rtData['code']],
                        $rtData
                    );
                    $keptRoomIds[] = $roomType->id;

                    $roomType->rates()->delete();
                    foreach ($rates as $rateRow) {
                        $roomType->rates()->create($rateRow);
                    }
                }

                if ($keptRoomIds !== []) {
                    $hotel->roomTypes()->whereNotIn('id', $keptRoomIds)->delete();
                }
            }

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: $existing === null ? 'hotel.created' : 'hotel.updated',
                    summary: "Saved tour-only hotel [{$hotel->name}]",
                    auditable: $hotel,
                    afterState: ['slug' => $hotel->slug, 'destination_id' => $hotel->destination_id, 'status' => $hotel->status],
                );
            }

            return $hotel->fresh(['destination', 'roomTypes.rates']);
        });
    }

    /**
     * Associate an eligible hotel with a tour overnight stop (strictly matching destination).
     *
     * @throws AuthorizationException
     */
    public function attachHotelToTourStay(
        ?User $actor,
        TourStay $stay,
        Hotel $hotel,
        string $optionType = TourStayHotel::OPTION_ALTERNATIVE,
        ?HotelRoomType $defaultRoomType = null,
        int $sortOrder = 0,
        ?string $notes = null,
    ): TourStayHotel {
        if ($actor !== null && ! ($actor->can('tours.update') || $actor->can('hotels.manage'))) {
            throw new AuthorizationException('You do not have permission to associate hotels with tour stays.');
        }

        if ($hotel->destination_id !== $stay->destination_id) {
            throw new InvalidArgumentException("Hotel [{$hotel->name}] does not belong to the tour stay's destination.");
        }

        if ($defaultRoomType !== null && $defaultRoomType->hotel_id !== $hotel->id) {
            throw new InvalidArgumentException('Default room type does not belong to the selected hotel.');
        }

        if (! in_array($optionType, [TourStayHotel::OPTION_DEFAULT, TourStayHotel::OPTION_ALTERNATIVE, TourStayHotel::OPTION_UPGRADE], true)) {
            $optionType = TourStayHotel::OPTION_ALTERNATIVE;
        }

        return DB::transaction(function () use ($stay, $hotel, $optionType, $defaultRoomType, $sortOrder, $notes): TourStayHotel {
            if ($optionType === TourStayHotel::OPTION_DEFAULT) {
                TourStayHotel::query()
                    ->where('tour_stay_id', $stay->id)
                    ->where('hotel_id', '!=', $hotel->id)
                    ->where('option_type', TourStayHotel::OPTION_DEFAULT)
                    ->update(['option_type' => TourStayHotel::OPTION_ALTERNATIVE]);
            }

            return TourStayHotel::query()->updateOrCreate(
                ['tour_stay_id' => $stay->id, 'hotel_id' => $hotel->id],
                [
                    'default_room_type_id' => $defaultRoomType?->id,
                    'option_type' => $optionType,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'notes' => $notes ? $this->sanitizer->sanitizePlainText($notes, 'stay_hotel.notes', 255) : null,
                ]
            );
        });
    }

    /**
     * Copy all active seasonal/yearly room rates from a source year to a target year without modifying history.
     *
     * @throws AuthorizationException
     */
    public function copyRatesToYear(User $actor, Hotel $hotel, int $sourceYear, int $targetYear): int
    {
        if (! $actor->can('hotels.manage')) {
            throw new AuthorizationException('You do not have permission to copy hotel room rates.');
        }

        if ($targetYear <= $sourceYear || $targetYear > 2100) {
            throw new InvalidArgumentException('Target year must be strictly greater than source year.');
        }

        $yearDelta = $targetYear - $sourceYear;
        $hotel->load('roomTypes.rates');

        return DB::transaction(function () use ($actor, $hotel, $sourceYear, $targetYear, $yearDelta): int {
            $copied = 0;

            foreach ($hotel->roomTypes as $roomType) {
                $sourceRates = $roomType->rates->filter(
                    fn (HotelRoomRate $rate): bool => $rate->is_active && $rate->valid_from->year === $sourceYear
                );

                foreach ($sourceRates as $rate) {
                    $newFrom = $rate->valid_from->addYears($yearDelta);
                    $newTo = $rate->valid_to->addYears($yearDelta);

                    $exists = HotelRoomRate::query()
                        ->where('hotel_room_type_id', $roomType->id)
                        ->where('rate_tier', $rate->rate_tier)
                        ->where('priority', $rate->priority)
                        ->whereDate('valid_from', '<=', $newTo->toDateString())
                        ->whereDate('valid_to', '>=', $newFrom->toDateString())
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $roomType->rates()->create([
                        'rate_name' => str_replace((string) $sourceYear, (string) $targetYear, $rate->rate_name),
                        'rate_tier' => $rate->rate_tier,
                        'valid_from' => $newFrom->toDateString(),
                        'valid_to' => $newTo->toDateString(),
                        'priority' => $rate->priority,
                        'nightly_rate_pkr' => $rate->nightly_rate_pkr,
                        'nightly_rate_usd' => $rate->nightly_rate_usd,
                        'is_active' => true,
                    ]);
                    $copied++;
                }
            }

            $this->audit->record(
                actor: $actor,
                event: 'hotel.rates_copied',
                summary: "Copied {$copied} room rate(s) for [{$hotel->name}] from {$sourceYear} to {$targetYear}",
                auditable: $hotel,
                afterState: ['source_year' => $sourceYear, 'target_year' => $targetYear, 'copied' => $copied],
            );

            return $copied;
        });
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateRoomTypes(array $rows): array
    {
        $sanitized = [];
        $seenCodes = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = $this->sanitizer->sanitizePlainText($row['name'] ?? null, 'room_type.name', 120);
            if ($name === '') {
                throw new InvalidArgumentException('Room type name is required.');
            }

            $code = strtoupper(Str::slug((string) ($row['code'] ?? $name), '_'));
            if (in_array($code, $seenCodes, true)) {
                throw new InvalidArgumentException("Duplicate room type code [{$code}].");
            }
            $seenCodes[] = $code;

            $maxOccupancy = (int) ($row['max_occupancy'] ?? 2);
            if ($maxOccupancy < 1 || $maxOccupancy > 20) {
                throw new InvalidArgumentException('Room max_occupancy must be between 1 and 20.');
            }

            $rates = $this->validateRates((array) ($row['rates'] ?? []));

            $sanitized[] = [
                'name' => $name,
                'code' => mb_substr($code, 0, 48),
                'max_occupancy' => $maxOccupancy,
                'bed_configuration' => $this->sanitizer->sanitizePlainText($row['bed_configuration'] ?? null, 'room_type.beds', 120) ?: null,
                'short_description' => $this->sanitizer->sanitizePlainText($row['short_description'] ?? null, 'room_type.desc', 400) ?: null,
                'is_active' => (bool) ($row['is_active'] ?? true),
                'rates' => $rates,
            ];
        }

        return $sanitized;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function validateRates(array $rows): array
    {
        $sanitized = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $from = CarbonImmutable::parse((string) ($row['valid_from'] ?? ''));
            $to = CarbonImmutable::parse((string) ($row['valid_to'] ?? ''));

            if ($to->lt($from)) {
                throw new InvalidArgumentException('Room rate valid_to date cannot precede valid_from date.');
            }

            $tier = (string) ($row['rate_tier'] ?? HotelRoomRate::TIER_STANDARD_YEARLY);
            if (! array_key_exists($tier, HotelRoomRate::TIER_PRECEDENCE)) {
                throw new InvalidArgumentException("Invalid room rate tier [{$tier}].");
            }

            $priority = max(1, (int) ($row['priority'] ?? 10));
            $pkr = $this->normalizePositiveDecimal($row['nightly_rate_pkr'] ?? null, 'nightly_rate_pkr');
            $usd = $this->normalizePositiveDecimal($row['nightly_rate_usd'] ?? null, 'nightly_rate_usd');

            if ($pkr === null && $usd === null) {
                throw new InvalidArgumentException('Room rate must specify a positive nightly PKR or USD price.');
            }

            $isActive = (bool) ($row['is_active'] ?? true);

            // Reject equal-tier, equal-priority overlapping date ranges for the same room type.
            if ($isActive) {
                foreach ($sanitized as $existing) {
                    if (
                        $existing['is_active']
                        && $existing['rate_tier'] === $tier
                        && $existing['priority'] === $priority
                        && $from->toDateString() <= $existing['valid_to']
                        && $to->toDateString() >= $existing['valid_from']
                    ) {
                        throw new InvalidArgumentException("Overlapping equal-priority room rates detected for tier [{$tier}] between {$from->toDateString()} and {$to->toDateString()}.");
                    }
                }
            }

            $sanitized[] = [
                'rate_name' => $this->sanitizer->sanitizePlainText($row['rate_name'] ?? 'Seasonal Rate', 'rate.name', 120),
                'rate_tier' => $tier,
                'valid_from' => $from->toDateString(),
                'valid_to' => $to->toDateString(),
                'priority' => $priority,
                'nightly_rate_pkr' => $pkr,
                'nightly_rate_usd' => $usd,
                'is_active' => $isActive,
            ];
        }

        return $sanitized;
    }

    private function normalizePositiveDecimal(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Field [{$field}] must be numeric.");
        }

        $formatted = number_format((float) $value, 2, '.', '');
        if (bccomp($formatted, '0.00', 2) <= 0) {
            throw new InvalidArgumentException("Field [{$field}] must be greater than zero.");
        }

        return $formatted;
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
            $url = $this->sanitizer->sanitizeUrl($item['url'] ?? null, 'hotel.gallery.url');
            if ($url === '') {
                continue;
            }
            $out[] = [
                'url' => $url,
                'alt' => $this->sanitizer->sanitizePlainText($item['alt'] ?? null, 'hotel.gallery.alt', 180),
                'caption' => $this->sanitizer->sanitizePlainText($item['caption'] ?? null, 'hotel.gallery.caption', 220),
            ];
        }

        return $out;
    }
}
