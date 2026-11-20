<?php

namespace App\Modules\Cms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsPage extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'slug',
        'is_homepage',
        'status',
        'published_version_id',
        'draft_version_id',
        'scheduled_publish_at',
        'published_at',
        'seo_title',
        'seo_description',
        'canonical_url',
        'og_image_url',
        'noindex',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_homepage' => 'boolean',
            'noindex' => 'boolean',
            'scheduled_publish_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CmsPageVersion::class, 'page_id')->orderByDesc('version_number');
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(CmsPageVersion::class, 'published_version_id');
    }

    public function draftVersion(): BelongsTo
    {
        return $this->belongsTo(CmsPageVersion::class, 'draft_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_version_id !== null;
    }
}
