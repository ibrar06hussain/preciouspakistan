<?php

namespace App\Modules\Booking\Models;

use App\Models\User;
use App\Modules\Tours\Models\Tour;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingQuote extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'quote_token',
        'idempotency_key',
        'tour_id',
        'user_id',
        'currency',
        'start_date',
        'end_date',
        'guests_count',
        'vehicle_days',
        'hotel_nights',
        'subtotal',
        'discount_amount',
        'tax_rate_percent',
        'tax_amount',
        'total_amount',
        'status',
        'expires_at',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'guests_count' => 'integer',
            'vehicle_days' => 'integer',
            'hotel_nights' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate_percent' => 'decimal:3',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === self::STATUS_EXPIRED;
    }
}
