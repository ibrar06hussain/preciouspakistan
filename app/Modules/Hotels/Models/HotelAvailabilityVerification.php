<?php

namespace App\Modules\Hotels\Models;

use App\Models\User;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourStay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelAvailabilityVerification extends Model
{
    public const STATUS_PENDING = 'pending_verification';

    public const STATUS_VERIFIED_AVAILABLE = 'verified_available';

    public const STATUS_UNAVAILABLE_ALTERNATIVE_PROPOSED = 'unavailable_alternative_proposed';

    public const STATUS_CONFIRMED_ALTERNATIVE = 'confirmed_alternative';

    public const STATUS_REJECTED_UNAVAILABLE = 'rejected_unavailable';

    public const METHODS = [
        'phone' => 'Phone Call',
        'email' => 'Email Confirmation',
        'whatsapp' => 'WhatsApp Business',
        'in_person' => 'In-Person / Local Desk',
    ];

    protected $fillable = [
        'reference_code',
        'tour_id',
        'tour_stay_id',
        'booking_id',
        'requested_hotel_id',
        'requested_room_type_id',
        'confirmed_hotel_id',
        'confirmed_room_type_id',
        'check_in_date',
        'nights_count',
        'rooms_count',
        'guests_count',
        'status',
        'verification_method',
        'external_reference',
        'verified_by',
        'verified_at',
        'officer_notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'nights_count' => 'integer',
            'rooms_count' => 'integer',
            'guests_count' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function tourStay(): BelongsTo
    {
        return $this->belongsTo(TourStay::class, 'tour_stay_id');
    }

    public function requestedHotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'requested_hotel_id');
    }

    public function requestedRoomType(): BelongsTo
    {
        return $this->belongsTo(HotelRoomType::class, 'requested_room_type_id');
    }

    public function confirmedHotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'confirmed_hotel_id');
    }

    public function confirmedRoomType(): BelongsTo
    {
        return $this->belongsTo(HotelRoomType::class, 'confirmed_room_type_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isExternallyConfirmed(): bool
    {
        return in_array($this->status, [self::STATUS_VERIFIED_AVAILABLE, self::STATUS_CONFIRMED_ALTERNATIVE], true)
            && $this->verified_by !== null
            && $this->verified_at !== null
            && filled($this->verification_method);
    }
}
