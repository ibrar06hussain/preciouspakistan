<?php

namespace App\Modules\Nomads\Services;

use App\Models\User;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Nomads\Models\NomadLocation;
use App\Modules\Rbac\Services\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class NomadCatalogService
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
    public function saveLocation(?User $actor, ?NomadLocation $existing, array $data): NomadLocation
    {
        if ($actor !== null && ! $actor->can('nomads.manage')) {
            throw new AuthorizationException('You do not have permission to manage Digital Nomad locations.');
        }

        $destination = Destination::query()->find((int) ($data['destination_id'] ?? $existing?->destination_id));
        if ($destination === null) {
            throw new InvalidArgumentException('Digital Nomad location must belong to a valid Pakistan destination.');
        }

        $name = $this->sanitizer->sanitizePlainText($data['name'] ?? $existing?->name ?? '', 'nomad.name', 160);
        if ($name === '') {
            throw new InvalidArgumentException('Nomad workspace location name is required.');
        }

        $slug = Str::slug((string) ($data['slug'] ?? $existing?->slug ?? $name));

        return DB::transaction(function () use ($actor, $existing, $destination, $data, $name, $slug): NomadLocation {
            $location = $existing ?? new NomadLocation;
            $location->fill([
                'name' => $name,
                'slug' => $slug,
                'region_id' => $destination->region_id,
                'destination_id' => $destination->id,
                'address' => $this->sanitizer->sanitizePlainText($data['address'] ?? $existing?->address, 'nomad.address', 255) ?: null,
                'latitude' => filled($data['latitude'] ?? null) ? round((float) $data['latitude'], 7) : $existing?->latitude,
                'longitude' => filled($data['longitude'] ?? null) ? round((float) $data['longitude'], 7) : $existing?->longitude,
                'power_source' => $this->sanitizer->sanitizePlainText($data['power_source'] ?? $existing?->power_source, 'nomad.power', 140) ?: null,
                'backup_power_details' => $this->sanitizer->sanitizePlainText($data['backup_power_details'] ?? $existing?->backup_power_details, 'nomad.backup', 255) ?: null,
                'power_guaranteed' => (bool) ($data['power_guaranteed'] ?? $existing?->power_guaranteed ?? false),
                'internet_provider' => $this->sanitizer->sanitizePlainText($data['internet_provider'] ?? $existing?->internet_provider, 'nomad.isp', 120) ?: null,
                'advertised_download_mbps' => filled($data['advertised_download_mbps'] ?? null) ? (int) $data['advertised_download_mbps'] : $existing?->advertised_download_mbps,
                'advertised_upload_mbps' => filled($data['advertised_upload_mbps'] ?? null) ? (int) $data['advertised_upload_mbps'] : $existing?->advertised_upload_mbps,
                'internet_guaranteed' => (bool) ($data['internet_guaranteed'] ?? $existing?->internet_guaranteed ?? false),
                'connectivity_warning' => $this->sanitizer->sanitizePlainText($data['connectivity_warning'] ?? $existing?->connectivity_warning, 'nomad.warning', 320) ?: null,
                'operating_hours' => $this->sanitizer->sanitizePlainText($data['operating_hours'] ?? $existing?->operating_hours, 'nomad.hours', 160) ?: null,
                'contact_info' => $this->sanitizer->sanitizePlainText($data['contact_info'] ?? $existing?->contact_info, 'nomad.contact', 160) ?: null,
                'description_html' => filled($data['description_html'] ?? null)
                    ? $this->sanitizer->sanitizeRichHtml((string) $data['description_html'], 'nomad.description_html')
                    : $existing?->description_html,
                'status' => in_array($data['status'] ?? $existing?->status ?? 'active', ['active', 'inactive'], true)
                    ? ($data['status'] ?? $existing?->status ?? 'active')
                    : 'active',
                'is_sample_content' => (bool) ($data['is_sample_content'] ?? $existing?->is_sample_content ?? true),
                'seo_title' => $this->sanitizer->sanitizePlainText($data['seo_title'] ?? $existing?->seo_title, 'nomad.seo_title', 180) ?: null,
                'seo_description' => $this->sanitizer->sanitizePlainText($data['seo_description'] ?? $existing?->seo_description, 'nomad.seo_description', 320) ?: null,
                'created_by' => $existing?->created_by ?? $actor?->id,
                'updated_by' => $actor?->id ?? $existing?->updated_by,
            ])->save();

            if (array_key_exists('facilities', $data)) {
                $keptIds = [];
                foreach ((array) $data['facilities'] as $row) {
                    if (! is_array($row) || blank($row['name'] ?? null)) {
                        continue;
                    }
                    $code = strtoupper(Str::slug((string) ($row['code'] ?? $row['name']), '_'));
                    $facility = $location->facilities()->updateOrCreate(
                        ['code' => $code],
                        [
                            'name' => $this->sanitizer->sanitizePlainText((string) $row['name'], 'facility.name', 140),
                            'facility_type' => (string) ($row['facility_type'] ?? 'hot_desk'),
                            'seat_capacity' => max(1, (int) ($row['seat_capacity'] ?? 10)),
                            'allows_daily' => (bool) ($row['allows_daily'] ?? true),
                            'allows_hourly' => (bool) ($row['allows_hourly'] ?? true),
                            'requires_manual_confirmation' => (bool) ($row['requires_manual_confirmation'] ?? true),
                            'description' => $this->sanitizer->sanitizePlainText($row['description'] ?? null, 'facility.desc', 400) ?: null,
                            'is_active' => (bool) ($row['is_active'] ?? true),
                        ]
                    );
                    $keptIds[] = $facility->id;

                    if (array_key_exists('rates', $row)) {
                        $facility->rates()->delete();
                        foreach ((array) $row['rates'] as $rate) {
                            if (! is_array($rate)) {
                                continue;
                            }
                            $facility->rates()->create([
                                'rate_name' => $this->sanitizer->sanitizePlainText($rate['rate_name'] ?? 'Standard Rate', 'rate.name', 120),
                                'rate_tier' => (string) ($rate['rate_tier'] ?? 'standard_yearly'),
                                'valid_from' => (string) $rate['valid_from'],
                                'valid_to' => (string) $rate['valid_to'],
                                'priority' => max(1, (int) ($rate['priority'] ?? 10)),
                                'daily_rate_pkr' => filled($rate['daily_rate_pkr'] ?? null) ? number_format((float) $rate['daily_rate_pkr'], 2, '.', '') : null,
                                'daily_rate_usd' => filled($rate['daily_rate_usd'] ?? null) ? number_format((float) $rate['daily_rate_usd'], 2, '.', '') : null,
                                'hourly_rate_pkr' => filled($rate['hourly_rate_pkr'] ?? null) ? number_format((float) $rate['hourly_rate_pkr'], 2, '.', '') : null,
                                'hourly_rate_usd' => filled($rate['hourly_rate_usd'] ?? null) ? number_format((float) $rate['hourly_rate_usd'], 2, '.', '') : null,
                                'is_active' => (bool) ($rate['is_active'] ?? true),
                            ]);
                        }
                    }
                }
                if ($keptIds !== []) {
                    $location->facilities()->whereNotIn('id', $keptIds)->delete();
                }
            }

            if ($actor !== null) {
                $this->audit->record(
                    actor: $actor,
                    event: $existing === null ? 'nomad.location_created' : 'nomad.location_updated',
                    summary: "Saved Digital Nomad workspace [{$location->name}]",
                    auditable: $location,
                    afterState: ['slug' => $location->slug, 'destination_id' => $location->destination_id],
                );
            }

            return $location->fresh(['destination', 'facilities.rates', 'blackouts']);
        });
    }
}
