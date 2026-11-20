<?php

namespace App\Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $fillable = [
        'uuid',
        'sha256_hash',
        'original_filename',
        'disk',
        'original_path',
        'webp_path',
        'avif_path',
        'variants',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'focal_x',
        'focal_y',
        'alt_text',
        'caption',
        'license_attribution',
        'preserve_original',
        'is_sample_asset',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'focal_x' => 'decimal:2',
            'focal_y' => 'decimal:2',
            'preserve_original' => 'boolean',
            'is_sample_asset' => 'boolean',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class, 'media_asset_id');
    }

    public function preferredUrl(): ?string
    {
        $path = $this->webp_path ?? $this->original_path;

        return $path !== null ? Storage::disk($this->disk)->url($path) : null;
    }
}
