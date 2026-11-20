<?php

namespace App\Modules\Seo\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'meta_title',
        'meta_description',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_url',
        'twitter_card',
        'robots',
        'include_in_sitemap',
        'schema_type',
        'editorial_html',
    ];

    protected function casts(): array
    {
        return [
            'include_in_sitemap' => 'boolean',
        ];
    }
}
