<?php

namespace App\Modules\Tours\Models;

use App\Modules\Destinations\Models\Destination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourItineraryDay extends Model
{
    protected $fillable = [
        'tour_id',
        'day_number',
        'title',
        'destination_id',
        'description_html',
        'meals_included',
        'altitude_meters',
        'travel_distance_km',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'altitude_meters' => 'integer',
            'travel_distance_km' => 'integer',
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
}
