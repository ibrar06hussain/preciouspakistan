<?php

namespace App\Modules\Fleet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleSeasonalRate extends Model
{
    public const TIER_DEFAULT = 'default';

    public const TIER_STANDARD_YEARLY = 'standard_yearly';

    public const TIER_SEASONAL_YEARLY = 'seasonal_yearly';

    public const TIER_SPECIAL = 'special';

    /**
     * Authoritative pricing precedence: special > seasonal_yearly > standard_yearly > default.
     *
     * @var array<string, int>
     */
    public const TIER_PRECEDENCE = [
        self::TIER_DEFAULT => 10,
        self::TIER_STANDARD_YEARLY => 20,
        self::TIER_SEASONAL_YEARLY => 30,
        self::TIER_SPECIAL => 40,
    ];

    protected $fillable = [
        'vehicle_id',
        'rate_name',
        'rate_tier',
        'valid_from',
        'valid_to',
        'priority',
        'with_fuel_rate_pkr',
        'without_fuel_rate_pkr',
        'with_fuel_rate_usd',
        'without_fuel_rate_usd',
        'daily_mileage_allowance_km',
        'excess_km_rate_pkr',
        'excess_km_rate_usd',
        'excess_km_terms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'priority' => 'integer',
            'with_fuel_rate_pkr' => 'decimal:2',
            'without_fuel_rate_pkr' => 'decimal:2',
            'with_fuel_rate_usd' => 'decimal:2',
            'without_fuel_rate_usd' => 'decimal:2',
            'daily_mileage_allowance_km' => 'integer',
            'excess_km_rate_pkr' => 'decimal:2',
            'excess_km_rate_usd' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function tierPrecedence(): int
    {
        return self::TIER_PRECEDENCE[$this->rate_tier] ?? 10;
    }
}
