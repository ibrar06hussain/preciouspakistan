<?php

namespace App\Modules\Nomads\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NomadSeasonalRate extends Model
{
    public const TIER_PRECEDENCE = [
        'default' => 10,
        'standard_yearly' => 20,
        'seasonal_yearly' => 30,
        'special' => 40,
    ];

    protected $fillable = [
        'nomad_facility_id',
        'rate_name',
        'rate_tier',
        'valid_from',
        'valid_to',
        'priority',
        'daily_rate_pkr',
        'daily_rate_usd',
        'hourly_rate_pkr',
        'hourly_rate_usd',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'priority' => 'integer',
            'daily_rate_pkr' => 'decimal:2',
            'daily_rate_usd' => 'decimal:2',
            'hourly_rate_pkr' => 'decimal:2',
            'hourly_rate_usd' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(NomadFacility::class, 'nomad_facility_id');
    }

    public function tierPrecedence(): int
    {
        return self::TIER_PRECEDENCE[$this->rate_tier] ?? 10;
    }
}
