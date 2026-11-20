<?php

namespace App\Modules\Destinations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    protected $table = 'geo_destinations';

    protected $fillable = [
        'region_id',
        'district_id',
        'name',
        'slug',
        'tagline',
        'summary',
        'description_html',
        'elevation_meters',
        'best_season',
        'hero_image_url',
        'gallery',
        'latitude',
        'longitude',
        'geojson',
        'is_featured',
        'is_active',
        'is_sample_content',
        'seo_title',
        'seo_description',
        'canonical_url',
        'og_image_url',
        'noindex',
    ];

    protected function casts(): array
    {
        return [
            'elevation_meters' => 'integer',
            'gallery' => 'array',
            'geojson' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'is_sample_content' => 'boolean',
            'noindex' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function attractions(): HasMany
    {
        return $this->hasMany(Attraction::class, 'destination_id')->orderBy('name');
    }

    public function publicUrl(): string
    {
        return route('destinations.show', ['slug' => $this->slug]);
    }
}
