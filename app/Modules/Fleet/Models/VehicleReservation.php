<?php

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Tours\Models\Tour;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleReservation extends Model
{
    public const STATUS_HOLD = 'hold';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_RELEASED = 'released';

    public const STATUS_CANCELLED = 'cancelled';

    public const FUEL_WITH = 'with_fuel';

    public const FUEL_WITHOUT = 'without_fuel';

    protected $fillable = [
        'reference_code',
        'vehicle_id',
        'booking_id',
        'tour_id',
        'start_date',
        'end_date',
        'fuel_mode',
        'status',
        'expires_at',
        'quoted_currency',
        'quoted_total',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'expires_at' => 'datetime',
            'quoted_total' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
