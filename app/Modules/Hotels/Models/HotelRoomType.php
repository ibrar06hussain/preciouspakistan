<?php

namespace App\Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotelRoomType extends Model
{
    protected $fillable = [
        'hotel_id',
        'name',
        'code',
        'max_occupancy',
        'bed_configuration',
        'short_description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_occupancy' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(HotelRoomRate::class, 'hotel_room_type_id')->orderBy('valid_from');
    }
}
