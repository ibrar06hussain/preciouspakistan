<?php

namespace App\Modules\Tours\Services;

use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourDeparture;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class TourSearchService
{
    /**
     * Search published tours only with multi-facet filters.
     *
     * @param  array{q?: ?string, category?: ?string, region?: ?string, destination?: ?string, difficulty?: ?string, guests?: ?int, nomad?: bool, sort?: ?string}  $filters
     * @return Collection<int, Tour>
     */
    public function searchPublished(array $filters = []): Collection
    {
        $query = Tour::query()
            ->where('status', Tour::STATUS_PUBLISHED)
            ->with(['destinations.region'])
            ->withCount('itineraryDays');

        if (filled($filters['category'] ?? null)) {
            $query->where('category', strtolower(trim((string) $filters['category'])));
        }

        if (filled($filters['difficulty'] ?? null)) {
            $query->where('difficulty', strtolower(trim((string) $filters['difficulty'])));
        }

        if (filled($filters['region'] ?? null)) {
            $regionSlug = strtolower(trim((string) $filters['region']));
            $query->whereHas('destinations.region', fn (Builder $q) => $q->where('slug', $regionSlug));
        }

        if (filled($filters['destination'] ?? null)) {
            $destSlug = strtolower(trim((string) $filters['destination']));
            $query->whereHas('destinations', fn (Builder $q) => $q->where('slug', $destSlug));
        }

        if (! empty($filters['guests'])) {
            $guests = max(1, (int) $filters['guests']);
            $query->where('min_guests', '<=', $guests)->where('max_guests', '>=', $guests);
        }

        if (! empty($filters['nomad'])) {
            $query->where('supports_nomad_addon', true);
        }

        if (filled($filters['q'] ?? null)) {
            $term = '%'.trim((string) $filters['q']).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('title', 'like', $term)
                    ->orWhere('summary', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhereHas('destinations', fn (Builder $dq) => $dq->where('name', 'like', $term));
            });
        }

        match ($filters['sort'] ?? 'featured') {
            'duration_asc' => $query->orderBy('duration_days')->orderBy('title'),
            'duration_desc' => $query->orderByDesc('duration_days')->orderBy('title'),
            'price_asc' => $query->orderByRaw('CASE WHEN base_price_pkr IS NULL THEN 1 ELSE 0 END')->orderBy('base_price_pkr'),
            default => $query->orderByDesc('is_featured')->orderByDesc('published_at')->orderBy('title'),
        };

        return $query->get();
    }

    /**
     * Validate guest count, start date lead/window constraints, and optional departure capacity.
     *
     * @return array{tour_id: int, start_date: string, end_date: string, guests: int, departure_id: ?int, currency: string}
     */
    public function validateBookingEntry(
        Tour $tour,
        string $startDateInput,
        int $guests,
        ?int $departureId = null,
        string $currency = 'PKR',
    ): array {
        if (! $tour->isPublished()) {
            throw new InvalidArgumentException('This tour is not currently open for booking requests.');
        }

        if ($guests < $tour->min_guests || $guests > $tour->max_guests) {
            throw new InvalidArgumentException("Guest count must be between {$tour->min_guests} and {$tour->max_guests} for this tour.");
        }

        $currency = strtoupper(trim($currency));
        if (! in_array($currency, ['PKR', 'USD'], true)) {
            $currency = 'PKR';
        }

        $today = CarbonImmutable::today('UTC');
        $earliest = $today->addDays($tour->booking_lead_days);
        $latest = $today->addDays($tour->booking_window_days);

        if ($departureId !== null) {
            $departure = TourDeparture::query()
                ->where('tour_id', $tour->id)
                ->find($departureId);

            if ($departure === null || $departure->status !== TourDeparture::STATUS_OPEN) {
                throw new InvalidArgumentException('Selected departure is unavailable.');
            }

            if ($departure->remainingSeats() < $guests) {
                throw new InvalidArgumentException("Only {$departure->remainingSeats()} seat(s) remain on departure {$departure->departure_code}.");
            }

            $startDate = CarbonImmutable::instance($departure->start_date);
            $endDate = CarbonImmutable::instance($departure->end_date);
        } else {
            $startDate = CarbonImmutable::parse($startDateInput, 'UTC')->startOfDay();
            $endDate = $startDate->addDays(max(0, $tour->duration_days - 1));
        }

        if ($startDate->lt($earliest)) {
            throw new InvalidArgumentException("Bookings require at least {$tour->booking_lead_days} day(s) advance notice (earliest: {$earliest->toDateString()}).");
        }

        if ($startDate->gt($latest)) {
            throw new InvalidArgumentException("Start date exceeds the allowed {$tour->booking_window_days}-day booking window.");
        }

        return [
            'tour_id' => $tour->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'guests' => $guests,
            'departure_id' => $departureId,
            'currency' => $currency,
        ];
    }
}
