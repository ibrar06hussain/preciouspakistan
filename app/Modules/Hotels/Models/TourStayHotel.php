<?php

namespace App\Modules\Hotels\Models;

use App\Modules\Tours\Models\TourStay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourStayHotel extends Model
{
    public const OPTION_DEFAULT = 'default';

    public const OPTION_ALTERNATIVE = 'alternative';

    public const OPTION_UPGRADE = 'upgrade';

    protected $fillable = [
        'tour_stay_id',
        'hotel_id',
        'default_room_type_id',
        'option_type',
        'sort_order',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tourStay(): BelongsTo
    {
        return $this->belongsTo(TourStay::class, 'tour_stay_id');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function defaultRoomType(): BelongsTo
    {
        return $this->belongsTo(HotelRoomType::class, 'default_room_type_id');
    }
}
