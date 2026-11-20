<?php

namespace App\Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelRoomRate extends Model
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
        'hotel_room_type_id',
        'rate_name',
        'rate_tier',
        'valid_from',
        'valid_to',
        'priority',
        'nightly_rate_pkr',
        'nightly_rate_usd',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'priority' => 'integer',
            'nightly_rate_pkr' => 'decimal:2',
            'nightly_rate_usd' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(HotelRoomType::class, 'hotel_room_type_id');
    }

    public function tierPrecedence(): int
    {
        return self::TIER_PRECEDENCE[$this->rate_tier] ?? 10;
    }
}
