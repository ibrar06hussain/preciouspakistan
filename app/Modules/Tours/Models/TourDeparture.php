<?php

namespace App\Modules\Tours\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourDeparture extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_FULL = 'full';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tour_id',
        'departure_code',
        'start_date',
        'end_date',
        'capacity',
        'reserved_seats',
        'price_override_pkr',
        'price_override_usd',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
            'reserved_seats' => 'integer',
            'price_override_pkr' => 'decimal:2',
            'price_override_usd' => 'decimal:2',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function remainingSeats(): int
    {
        return max(0, $this->capacity - $this->reserved_seats);
    }
}
