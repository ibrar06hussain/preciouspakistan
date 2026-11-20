<?php

namespace App\Modules\Fleet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_RETIRED = 'retired';

    public const TYPES = [
        'sedan' => 'Executive Sedan',
        'suv_4x4' => '4x4 Mountain SUV',
        'jeep_4x4' => 'High-Altitude 4x4 Jeep',
        'grand_cabin' => 'HiAce / Grand Cabin Van',
        'coaster' => 'Coaster Saloon Bus',
        'luxury_suv' => 'Luxury 4x4 SUV',
    ];

    public const ROUTE_TAGS = [
        'city' => 'City & Airport Transfers',
        'highway' => 'Paved Highways & Motorways',
        'karakoram' => 'Karakoram Highway & Valleys',
        'high_altitude_4x4' => 'High-Altitude / Off-Road (Deosai, Fairy Meadows Access)',
        'coastal' => 'Makran Coastal Highway',
    ];

    protected $fillable = [
        'code',
        'name',
        'slug',
        'vehicle_type',
        'make',
        'model',
        'model_year',
        'registration_number',
        'internal_fleet_tag',
        'private_notes',
        'passenger_capacity',
        'luggage_capacity',
        'transmission',
        'has_ac',
        'amenities',
        'routing_suitability',
        'driver_always_included',
        'status',
        'summary',
        'description_html',
        'hero_image_url',
        'gallery',
        'is_sample_content',
        'seo_title',
        'seo_description',
        'canonical_url',
        'noindex',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'registration_number',
        'internal_fleet_tag',
        'private_notes',
    ];

    protected function casts(): array
    {
        return [
            'model_year' => 'integer',
            'passenger_capacity' => 'integer',
            'luggage_capacity' => 'integer',
            'has_ac' => 'boolean',
            'amenities' => 'array',
            'routing_suitability' => 'array',
            'driver_always_included' => 'boolean',
            'gallery' => 'array',
            'is_sample_content' => 'boolean',
            'noindex' => 'boolean',
        ];
    }

    public function driverAssignments(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class, 'vehicle_id')->orderByDesc('is_primary')->orderByDesc('assigned_from');
    }

    public function seasonalRates(): HasMany
    {
        return $this->hasMany(VehicleSeasonalRate::class, 'vehicle_id')->orderBy('valid_from');
    }

    public function routeAdjustments(): HasMany
    {
        return $this->hasMany(VehicleRouteAdjustment::class, 'vehicle_id')->orderBy('route_label');
    }

    public function blackouts(): HasMany
    {
        return $this->hasMany(VehicleBlackout::class, 'vehicle_id')->orderBy('start_date');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(VehicleReservation::class, 'vehicle_id')->orderByDesc('start_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
