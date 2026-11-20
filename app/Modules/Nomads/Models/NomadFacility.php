<?php

namespace App\Modules\Nomads\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NomadFacility extends Model
{
    public const TYPES = [
        'hot_desk' => 'Coworking Desk Seat',
        'dedicated_desk' => 'Dedicated Desk',
        'private_office' => 'Private Office',
        'meeting_room' => 'Meeting / Call Room',
    ];

    protected $fillable = [
        'nomad_location_id',
        'name',
        'code',
        'facility_type',
        'seat_capacity',
        'allows_daily',
        'allows_hourly',
        'requires_manual_confirmation',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'seat_capacity' => 'integer',
            'allows_daily' => 'boolean',
            'allows_hourly' => 'boolean',
            'requires_manual_confirmation' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(NomadLocation::class, 'nomad_location_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(NomadSeasonalRate::class, 'nomad_facility_id')->orderBy('valid_from');
    }
}
