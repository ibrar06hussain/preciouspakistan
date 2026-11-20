<?php

namespace App\Modules\Destinations\Services;

use App\Modules\Destinations\Models\Attraction;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\Region;
use App\Modules\Tours\Models\Tour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class GeoSearchService
{
    /**
     * Filter active destinations by region slug/id, district slug/id, keyword, or featured status.
     *
     * @param  array{region?: ?string, district?: ?string, q?: ?string, featured?: bool}  $filters
     * @return Collection<int, Destination>
     */
    public function searchDestinations(array $filters = []): Collection
    {
        $query = Destination::query()
            ->where('is_active', true)
            ->with(['region', 'district'])
            ->withCount(['attractions' => fn (Builder $q) => $q->where('is_active', true)]);

        if (filled($filters['region'] ?? null)) {
            $regionSlug = strtolower(trim((string) $filters['region']));
            $query->whereHas('region', fn (Builder $q) => $q->where('slug', $regionSlug)->where('is_active', true));
        }

        if (filled($filters['district'] ?? null)) {
            $districtSlug = strtolower(trim((string) $filters['district']));
            $query->whereHas('district', fn (Builder $q) => $q->where('slug', $districtSlug));
        }

        if (filled($filters['q'] ?? null)) {
            $term = '%'.trim((string) $filters['q']).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('tagline', 'like', $term)
                    ->orWhere('summary', 'like', $term)
                    ->orWhereHas('region', fn (Builder $rq) => $rq->where('name', 'like', $term))
                    ->orWhereHas('attractions', fn (Builder $aq) => $aq->where('name', 'like', $term));
            });
        }

        if (! empty($filters['featured'])) {
            $query->where('is_featured', true);
        }

        return $query->orderByDesc('is_featured')->orderBy('name')->get();
    }

    /**
     * Find active attractions belonging to or geographically near a destination using Haversine distance (km).
     *
     * @return Collection<int, array{attraction: Attraction, distance_km: ?float, is_local: bool}>
     */
    public function nearbyAttractions(Destination $destination, float $radiusKm = 120.0, int $limit = 12): Collection
    {
        $attractions = Attraction::query()
            ->where('is_active', true)
            ->with('destination.region')
            ->get();

        $results = collect();

        foreach ($attractions as $attraction) {
            $isLocal = $attraction->destination_id === $destination->id;
            $distanceKm = null;

            if (
                $destination->latitude !== null
                && $destination->longitude !== null
                && $attraction->latitude !== null
                && $attraction->longitude !== null
            ) {
                $distanceKm = round($this->haversineKm(
                    $destination->latitude,
                    $destination->longitude,
                    $attraction->latitude,
                    $attraction->longitude
                ), 1);
            }

            if ($isLocal || ($distanceKm !== null && $distanceKm <= $radiusKm)) {
                $results->push([
                    'attraction' => $attraction,
                    'distance_km' => $distanceKm,
                    'is_local' => $isLocal,
                ]);
            }
        }

        return $results
            ->sortBy(fn (array $item): float => $item['is_local'] ? ($item['distance_km'] ?? 0.0) : 1000.0 + ($item['distance_km'] ?? 999.0))
            ->take($limit)
            ->values();
    }

    /**
     * Build an RFC 7946 GeoJSON FeatureCollection for Leaflet map rendering.
     *
     * @param  Collection<int, Destination>  $destinations
     * @param  Collection<int, Attraction>|null  $attractions
     * @return array{type: string, features: list<array<string, mixed>>}
     */
    public function buildGeoJson(Collection $destinations, ?Collection $attractions = null): array
    {
        $features = [];

        foreach ($destinations as $destination) {
            if ($destination->latitude === null || $destination->longitude === null) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $destination->longitude, (float) $destination->latitude],
                ],
                'properties' => [
                    'kind' => 'destination',
                    'id' => $destination->id,
                    'name' => $destination->name,
                    'slug' => $destination->slug,
                    'region' => $destination->region?->name,
                    'summary' => $destination->summary,
                    'url' => $destination->publicUrl(),
                ],
            ];
        }

        if ($attractions !== null) {
            foreach ($attractions as $attraction) {
                if ($attraction->latitude === null || $attraction->longitude === null) {
                    continue;
                }

                $features[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $attraction->longitude, (float) $attraction->latitude],
                    ],
                    'properties' => [
                        'kind' => 'attraction',
                        'id' => $attraction->id,
                        'name' => $attraction->name,
                        'category' => Attraction::CATEGORIES[$attraction->category] ?? ucfirst($attraction->category),
                        'summary' => $attraction->summary,
                    ],
                ];
            }
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    /**
     * Returns live published tours linked to the given destination.
     *
     * @return Collection<int, Tour>
     */
    public function relatedToursForDestination(Destination $destination): Collection
    {
        if (! Schema::hasTable('tours')) {
            return collect();
        }

        return Tour::query()
            ->where('status', Tour::STATUS_PUBLISHED)
            ->whereHas('destinations', fn (Builder $q) => $q->where('geo_destinations.id', $destination->id))
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->limit(6)
            ->get();
    }

    public function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
