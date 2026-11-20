<?php

namespace App\Modules\Destinations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attraction extends Model
{
    protected $table = 'geo_attractions';

    public const CATEGORIES = [
        'landmark' => 'Landmark',
        'lake' => 'Lake',
        'valley' => 'Valley',
        'fort' => 'Historic Fort',
        'heritage' => 'Heritage Site',
        'pass' => 'Mountain Pass',
        'beach' => 'Coast & Beach',
        'park' => 'National Park',
        'viewpoint' => 'Scenic Viewpoint',
    ];

    protected $fillable = [
        'destination_id',
        'name',
        'slug',
        'category',
        'summary',
        'description_html',
        'gallery',
        'latitude',
        'longitude',
        'is_active',
        'is_sample_content',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'is_sample_content' => 'boolean',
        ];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }
}
