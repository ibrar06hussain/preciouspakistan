<?php

namespace App\Modules\Destinations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $table = 'geo_districts';

    protected $fillable = [
        'region_id',
        'name',
        'slug',
        'summary',
        'latitude',
        'longitude',
        'is_active',
        'is_sample_subset',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'is_sample_subset' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class, 'district_id')->orderBy('name');
    }
}
