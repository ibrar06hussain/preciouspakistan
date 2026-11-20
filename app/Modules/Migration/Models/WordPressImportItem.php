<?php

namespace App\Modules\Migration\Models;

use App\Modules\Destinations\Models\UrlRedirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordPressImportItem extends Model
{
    protected $table = 'wordpress_import_items';

    protected $fillable = [
        'batch_id',
        'source_wp_id',
        'source_post_type',
        'source_url',
        'source_slug',
        'target_type',
        'target_id',
        'redirect_id',
        'status',
        'warnings',
    ];

    protected function casts(): array
    {
        return [
            'warnings' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(WordPressImportBatch::class, 'batch_id');
    }

    public function redirect(): BelongsTo
    {
        return $this->belongsTo(UrlRedirect::class, 'redirect_id');
    }
}
