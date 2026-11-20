<?php

namespace App\Modules\Fleet\Models;

use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleRouteAdjustment extends Model
{
    public const MODE_FIXED_PER_TRIP = 'fixed_per_trip';

    public const MODE_PER_DAY = 'per_day';

    protected $fillable = [
        'vehicle_id',
        'region_id',
        'destination_id',
        'route_label',
        'adjustment_mode',
        'amount_pkr',
        'amount_usd',
        'valid_from',
        'valid_to',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_pkr' => 'decimal:2',
            'amount_usd' => 'decimal:2',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }
}
