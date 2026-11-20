<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPageSection extends Model
{
    protected $fillable = [
        'page_version_id',
        'section_key',
        'component_type',
        'sort_order',
        'is_visible',
        'device_visibility',
        'style_preset',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
            'config' => 'array',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(CmsPageVersion::class, 'page_version_id');
    }
}
