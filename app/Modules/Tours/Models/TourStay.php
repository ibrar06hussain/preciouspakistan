<?php

namespace App\Modules\Tours\Models;

use App\Modules\Destinations\Models\Destination;
use App\Modules\Hotels\Models\TourStayHotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourStay extends Model
{
    protected $fillable = [
        'tour_id',
        'destination_id',
        'stay_order',
        'check_in_day',
        'nights_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stay_order' => 'integer',
            'check_in_day' => 'integer',
            'nights_count' => 'integer',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    public function stayHotels(): HasMany
    {
        return $this->hasMany(TourStayHotel::class, 'tour_stay_id')->orderBy('sort_order')->orderBy('id');
    }
}
