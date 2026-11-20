<?php

namespace App\Modules\Fleet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDriverAssignment extends Model
{
    protected $fillable = [
        'vehicle_id',
        'driver_name',
        'driver_phone',
        'license_number',
        'assigned_from',
        'assigned_to',
        'is_primary',
        'notes',
    ];

    protected $hidden = [
        'driver_phone',
        'license_number',
    ];

    protected function casts(): array
    {
        return [
            'assigned_from' => 'date',
            'assigned_to' => 'date',
            'is_primary' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
