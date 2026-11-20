<?php

namespace App\Modules\Destinations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $table = 'geo_countries';

    protected $fillable = [
        'name',
        'iso_code',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class, 'country_id')->orderBy('sort_order')->orderBy('name');
    }
}
