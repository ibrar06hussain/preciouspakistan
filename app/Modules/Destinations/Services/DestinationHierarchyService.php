<?php

namespace App\Modules\Destinations\Services;

use App\Models\User;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Destinations\Models\Attraction;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\District;
use App\Modules\Destinations\Models\Region;
use App\Modules\Destinations\Models\UrlRedirect;
use App\Modules\Rbac\Services\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DestinationHierarchyService
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function saveRegion(?User $actor, ?Region $existing, array $data): Region
    {
        $this->authorizeManage($actor);

        $slug = $this->normalizeSlug((string) ($data['slug'] ?? $data['name'] ?? ''));
        $descriptionHtml = filled($data['description_html'] ?? null)
            ? $this->sanitizer->sanitizeRichHtml((string) $data['description_html'], 'region.description_html')
            : null;

        return DB::transaction(function () use ($actor, $existing, $data, $slug, $descriptionHtml): Region {
            $oldSlug = $existing?->slug;

            $region = $existing ?? new Region;
            $region->fill([
                'country_id' => (int) ($data['country_id'] ?? $existing?->country_id),
                'name' => $this->sanitizer->sanitizePlainText($data['name'] ?? '', 'region.name', 140),
                'region_type' => (string) ($data['region_type'] ?? $existing?->region_type ?? 'province'),
                'slug' => $slug,
                'tagline' => $this->sanitizer->sanitizePlainText($data['tagline'] ?? null, 'region.tagline', 220) ?: null,
                'summary' => $this->sanitizer->sanitizePlainText($data['summary'] ?? null, 'region.summary', 1000) ?: null,
                'description_html' => $descriptionHtml,
                'hero_image_url' => $this->sanitizer->sanitizeUrl($data['hero_image_url'] ?? null, 'region.hero_image_url') ?: null,
                'gallery' => $this->sanitizeGallery($data['gallery'] ?? null),
                'latitude' => $this->sanitizeCoord($data['latitude'] ?? null, -90, 90),
                'longitude' => $this->sanitizeCoord($data['longitude'] ?? null, -180, 180),
                'default_zoom' => max(4, min(16, (int) ($data['default_zoom'] ?? 7))),
                'geojson' => is_array($data['geojson'] ?? null) ? $data['geojson'] : $existing?->geojson,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'sort_order' => (int) ($data['sort_order'] ?? $existing?->sort_order ?? 0),
                'seo_title' => $this->sanitizer->sanitizePlainText($data['seo_title'] ?? null, 'region.seo_title', 180) ?: null,
                'seo_description' => $this->sanitizer->sanitizePlainText($data['seo_description'] ?? null, 'region.seo_description', 320) ?: null,
                'canonical_url' => $this->sanitizer->sanitizeUrl($data['canonical_url'] ?? null, 'region.canonical_url') ?: null,
                'og_image_url' => $this->sanitizer->sanitizeUrl($data['og_image_url'] ?? null, 'region.og_image_url') ?: null,
                'noindex' => (bool) ($data['noindex'] ?? false),
            ])->save();

            if ($oldSlug !== null && $oldSlug !== $region->slug) {
                $this->registerRedirect($actor, '/regions/'.$oldSlug, '/regions/'.$region->slug, 'region_slug_changed');
            }

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: $existing === null ? 'geo.region_created' : 'geo.region_updated',
                    summary: "Saved region [{$region->name}] ({$region->slug})",
                    auditable: $region,
                    beforeState: $oldSlug ? ['slug' => $oldSlug] : null,
                    afterState: ['slug' => $region->slug, 'is_active' => $region->is_active],
                );
            }

            return $region->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function saveDistrict(?User $actor, ?District $existing, array $data): District
    {
        $this->authorizeManage($actor);

        $regionId = (int) ($data['region_id'] ?? $existing?->region_id);
        if (! Region::query()->whereKey($regionId)->exists()) {
            throw new InvalidArgumentException('District must belong to an existing region.');
        }

        $slug = $this->normalizeSlug((string) ($data['slug'] ?? $data['name'] ?? ''));

        return DB::transaction(function () use ($actor, $existing, $data, $regionId, $slug): District {
            $district = $existing ?? new District;
            $district->fill([
                'region_id' => $regionId,
                'name' => $this->sanitizer->sanitizePlainText($data['name'] ?? '', 'district.name', 140),
                'slug' => $slug,
                'summary' => $this->sanitizer->sanitizePlainText($data['summary'] ?? null, 'district.summary', 1000) ?: null,
                'latitude' => $this->sanitizeCoord($data['latitude'] ?? null, -90, 90),
                'longitude' => $this->sanitizeCoord($data['longitude'] ?? null, -180, 180),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_sample_subset' => (bool) ($data['is_sample_subset'] ?? true),
                'seo_title' => $this->sanitizer->sanitizePlainText($data['seo_title'] ?? null, 'district.seo_title', 180) ?: null,
                'seo_description' => $this->sanitizer->sanitizePlainText($data['seo_description'] ?? null, 'district.seo_description', 320) ?: null,
            ])->save();

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: $existing === null ? 'geo.district_created' : 'geo.district_updated',
                    summary: "Saved district [{$district->name}]",
                    auditable: $district,
                    afterState: ['region_id' => $district->region_id, 'slug' => $district->slug],
                );
            }

            return $district->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function saveDestination(?User $actor, ?Destination $existing, array $data): Destination
    {
        $this->authorizeManage($actor);

        $regionId = (int) ($data['region_id'] ?? $existing?->region_id);
        $districtId = filled($data['district_id'] ?? null) ? (int) $data['district_id'] : null;

        if (! Region::query()->whereKey($regionId)->exists()) {
            throw new InvalidArgumentException('Destination must belong to a valid Pakistan region.');
        }

        if ($districtId !== null) {
            $district = District::query()->find($districtId);
            if ($district === null || $district->region_id !== $regionId) {
                throw new InvalidArgumentException('Selected district does not belong to the destination’s region.');
            }
        }

        $slug = $this->normalizeSlug((string) ($data['slug'] ?? $data['name'] ?? ''));
        $descriptionHtml = filled($data['description_html'] ?? null)
            ? $this->sanitizer->sanitizeRichHtml((string) $data['description_html'], 'destination.description_html')
            : null;

        return DB::transaction(function () use ($actor, $existing, $data, $regionId, $districtId, $slug, $descriptionHtml): Destination {
            $oldSlug = $existing?->slug;

            $destination = $existing ?? new Destination;
            $destination->fill([
                'region_id' => $regionId,
                'district_id' => $districtId,
                'name' => $this->sanitizer->sanitizePlainText($data['name'] ?? '', 'destination.name', 160),
                'slug' => $slug,
                'tagline' => $this->sanitizer->sanitizePlainText($data['tagline'] ?? null, 'destination.tagline', 220) ?: null,
                'summary' => $this->sanitizer->sanitizePlainText($data['summary'] ?? null, 'destination.summary', 1000) ?: null,
                'description_html' => $descriptionHtml,
                'elevation_meters' => filled($data['elevation_meters'] ?? null) ? max(0, (int) $data['elevation_meters']) : null,
                'best_season' => $this->sanitizer->sanitizePlainText($data['best_season'] ?? null, 'destination.best_season', 120) ?: null,
                'hero_image_url' => $this->sanitizer->sanitizeUrl($data['hero_image_url'] ?? null, 'destination.hero_image_url') ?: null,
                'gallery' => $this->sanitizeGallery($data['gallery'] ?? null),
                'latitude' => $this->sanitizeCoord($data['latitude'] ?? null, -90, 90),
                'longitude' => $this->sanitizeCoord($data['longitude'] ?? null, -180, 180),
                'geojson' => is_array($data['geojson'] ?? null) ? $data['geojson'] : $existing?->geojson,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_sample_content' => (bool) ($data['is_sample_content'] ?? true),
                'seo_title' => $this->sanitizer->sanitizePlainText($data['seo_title'] ?? null, 'destination.seo_title', 180) ?: null,
                'seo_description' => $this->sanitizer->sanitizePlainText($data['seo_description'] ?? null, 'destination.seo_description', 320) ?: null,
                'canonical_url' => $this->sanitizer->sanitizeUrl($data['canonical_url'] ?? null, 'destination.canonical_url') ?: null,
                'og_image_url' => $this->sanitizer->sanitizeUrl($data['og_image_url'] ?? null, 'destination.og_image_url') ?: null,
                'noindex' => (bool) ($data['noindex'] ?? false),
            ])->save();

            if ($oldSlug !== null && $oldSlug !== $destination->slug) {
                $this->registerRedirect($actor, '/destinations/'.$oldSlug, '/destinations/'.$destination->slug, 'destination_slug_changed');
            }

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: $existing === null ? 'geo.destination_created' : 'geo.destination_updated',
                    summary: "Saved destination [{$destination->name}] ({$destination->slug})",
                    auditable: $destination,
                    beforeState: $oldSlug ? ['slug' => $oldSlug] : null,
                    afterState: [
                        'slug' => $destination->slug,
                        'region_id' => $destination->region_id,
                        'district_id' => $destination->district_id,
                    ],
                );
            }

            return $destination->fresh(['region', 'district']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function saveAttraction(?User $actor, ?Attraction $existing, array $data): Attraction
    {
        $this->authorizeManage($actor);

        $destinationId = (int) ($data['destination_id'] ?? $existing?->destination_id);
        if (! Destination::query()->whereKey($destinationId)->exists()) {
            throw new InvalidArgumentException('Attraction must belong to an existing destination.');
        }

        $slug = $this->normalizeSlug((string) ($data['slug'] ?? $data['name'] ?? ''));
        $category = (string) ($data['category'] ?? 'landmark');
        if (! array_key_exists($category, Attraction::CATEGORIES)) {
            $category = 'landmark';
        }

        $descriptionHtml = filled($data['description_html'] ?? null)
            ? $this->sanitizer->sanitizeRichHtml((string) $data['description_html'], 'attraction.description_html')
            : null;

        return DB::transaction(function () use ($actor, $existing, $data, $destinationId, $slug, $category, $descriptionHtml): Attraction {
            $attraction = $existing ?? new Attraction;
            $attraction->fill([
                'destination_id' => $destinationId,
                'name' => $this->sanitizer->sanitizePlainText($data['name'] ?? '', 'attraction.name', 160),
                'slug' => $slug,
                'category' => $category,
                'summary' => $this->sanitizer->sanitizePlainText($data['summary'] ?? null, 'attraction.summary', 1000) ?: null,
                'description_html' => $descriptionHtml,
                'gallery' => $this->sanitizeGallery($data['gallery'] ?? null),
                'latitude' => $this->sanitizeCoord($data['latitude'] ?? null, -90, 90),
                'longitude' => $this->sanitizeCoord($data['longitude'] ?? null, -180, 180),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_sample_content' => (bool) ($data['is_sample_content'] ?? true),
                'seo_title' => $this->sanitizer->sanitizePlainText($data['seo_title'] ?? null, 'attraction.seo_title', 180) ?: null,
                'seo_description' => $this->sanitizer->sanitizePlainText($data['seo_description'] ?? null, 'attraction.seo_description', 320) ?: null,
            ])->save();

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: $existing === null ? 'geo.attraction_created' : 'geo.attraction_updated',
                    summary: "Saved attraction [{$attraction->name}]",
                    auditable: $attraction,
                    afterState: ['destination_id' => $attraction->destination_id, 'slug' => $attraction->slug],
                );
            }

            return $attraction->fresh('destination');
        });
    }

    public function registerRedirect(?User $actor, string $sourcePath, string $targetPath, string $reason = 'slug_changed'): UrlRedirect
    {
        $source = '/'.ltrim(strtolower(trim($sourcePath)), '/');
        $target = '/'.ltrim(strtolower(trim($targetPath)), '/');

        if ($source === $target) {
            throw new InvalidArgumentException('Redirect source and target paths cannot be identical.');
        }

        // Remove any opposite redirect that would create a redirect loop.
        UrlRedirect::query()->where('source_path', $target)->delete();

        // Collapse existing redirect chains pointing to $source so they jump straight to $target.
        UrlRedirect::query()->where('target_path', $source)->update(['target_path' => $target]);

        return UrlRedirect::query()->updateOrCreate(
            ['source_path' => $source],
            [
                'target_path' => $target,
                'status_code' => 301,
                'reason' => $reason,
                'is_active' => true,
                'created_by' => $actor?->id,
            ]
        );
    }

    /**
     * @throws AuthorizationException
     */
    private function authorizeManage(?User $actor): void
    {
        if ($actor !== null && ! $actor->can('destinations.manage')) {
            throw new AuthorizationException('You do not have permission to manage destinations.');
        }
    }

    private function normalizeSlug(string $input): string
    {
        $slug = Str::slug($input);

        if ($slug === '') {
            throw new InvalidArgumentException('A valid slug is required.');
        }

        return mb_substr($slug, 0, 191);
    }

    private function sanitizeCoord(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $float = (float) $value;
        if ($float < $min || $float > $max) {
            throw new InvalidArgumentException("Coordinate [{$float}] is out of valid range [{$min}, {$max}].");
        }

        return round($float, 7);
    }

    /**
     * @return list<array{url: string, alt: string, caption: string}>|null
     */
    private function sanitizeGallery(mixed $gallery): ?array
    {
        if (! is_array($gallery)) {
            return null;
        }

        $out = [];
        foreach (array_slice(array_values($gallery), 0, 20) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $url = $this->sanitizer->sanitizeUrl($item['url'] ?? null, 'gallery.url');
            if ($url === '') {
                continue;
            }
            $out[] = [
                'url' => $url,
                'alt' => $this->sanitizer->sanitizePlainText($item['alt'] ?? null, 'gallery.alt', 180),
                'caption' => $this->sanitizer->sanitizePlainText($item['caption'] ?? null, 'gallery.caption', 220),
            ];
        }

        return $out;
    }
}
