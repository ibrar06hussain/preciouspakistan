<?php

namespace App\Modules\Destinations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $table = 'geo_regions';

    protected $fillable = [
        'country_id',
        'name',
        'region_type',
        'slug',
        'tagline',
        'summary',
        'description_html',
        'hero_image_url',
        'gallery',
        'latitude',
        'longitude',
        'default_zoom',
        'geojson',
        'is_active',
        'sort_order',
        'seo_title',
        'seo_description',
        'canonical_url',
        'og_image_url',
        'noindex',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'geojson' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'default_zoom' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'noindex' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'region_id')->orderBy('name');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class, 'region_id')->orderBy('name');
    }

    public function publicUrl(): string
    {
        return route('regions.show', ['slug' => $this->slug]);
    }
}
