<?php

namespace App\Modules\Booking\Models;

use App\Models\User;
use App\Modules\Fleet\Models\VehicleReservation;
use App\Modules\Hotels\Models\HotelAvailabilityVerification;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourDeparture;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUND_REQUIRED = 'refund_required';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUND_PENDING = 'refund_pending';

    public const PAYMENT_REFUNDED = 'refunded';

    public const BOOKING_STATUSES = [
        self::STATUS_REQUESTED => 'Requested',
        self::STATUS_PENDING_VERIFICATION => 'Pending External Verification',
        self::STATUS_AWAITING_PAYMENT => 'Awaiting Payment',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_REFUND_REQUIRED => 'Refund Required (Hotel Unavailable)',
    ];

    public const PAYMENT_STATUSES = [
        self::PAYMENT_UNPAID => 'Unpaid',
        self::PAYMENT_PARTIAL => 'Partially Paid',
        self::PAYMENT_PAID => 'Paid',
        self::PAYMENT_REFUND_PENDING => 'Refund Pending',
        self::PAYMENT_REFUNDED => 'Refunded',
    ];

    protected $fillable = [
        'booking_reference',
        'idempotency_key',
        'quote_id',
        'user_id',
        'tour_id',
        'departure_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'start_date',
        'end_date',
        'guests_count',
        'currency',
        'booking_status',
        'payment_status',
        'subtotal',
        'discount_amount',
        'tax_rate_percent',
        'tax_amount',
        'total_amount',
        'pricing_snapshot',
        'vehicle_reservation_id',
        'confirmed_by',
        'confirmed_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'customer_notes',
        'officer_notes',
        'is_sample_content',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'guests_count' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate_percent' => 'decimal:3',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'pricing_snapshot' => 'array',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_sample_content' => 'boolean',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(BookingQuote::class, 'quote_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(TourDeparture::class, 'departure_id');
    }

    public function vehicleReservation(): BelongsTo
    {
        return $this->belongsTo(VehicleReservation::class, 'vehicle_reservation_id');
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class, 'booking_id')->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BookingEvent::class, 'booking_id')->orderByDesc('id');
    }

    public function hotelVerifications(): HasMany
    {
        return $this->hasMany(HotelAvailabilityVerification::class, 'booking_id');
    }
}
