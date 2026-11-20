<?php

namespace App\Modules\Nomads\Models;

use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NomadLocation extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'slug',
        'region_id',
        'destination_id',
        'address',
        'latitude',
        'longitude',
        'power_source',
        'backup_power_details',
        'power_guaranteed',
        'internet_provider',
        'advertised_download_mbps',
        'advertised_upload_mbps',
        'internet_guaranteed',
        'connectivity_warning',
        'operating_hours',
        'contact_info',
        'description_html',
        'gallery',
        'status',
        'is_sample_content',
        'seo_title',
        'seo_description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'power_guaranteed' => 'boolean',
            'internet_guaranteed' => 'boolean',
            'advertised_download_mbps' => 'integer',
            'advertised_upload_mbps' => 'integer',
            'gallery' => 'array',
            'is_sample_content' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(NomadFacility::class, 'nomad_location_id')->orderBy('name');
    }

    public function blackouts(): HasMany
    {
        return $this->hasMany(NomadBlackout::class, 'nomad_location_id')->orderBy('start_date');
    }

    /**
     * Explicit honest notice displayed publicly whenever grid power or mountain ISP is not guaranteed.
     */
    public function honestyNotice(): string
    {
        if (filled($this->connectivity_warning)) {
            return (string) $this->connectivity_warning;
        }

        $warnings = [];
        if (! $this->internet_guaranteed) {
            $warnings[] = 'Internet throughput and uptime are not guaranteed in mountain weather conditions.';
        }
        if (! $this->power_guaranteed) {
            $warnings[] = 'Continuous grid power is not guaranteed; backup runtime depends on solar/UPS/generator load.';
        }

        return $warnings !== []
            ? implode(' ', $warnings)
            : 'Redundant power and fiber connectivity are reported by the facility operator; confirm SLA with your booking officer.';
    }
}
