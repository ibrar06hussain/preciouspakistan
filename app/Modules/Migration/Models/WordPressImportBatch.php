<?php

namespace App\Modules\Migration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WordPressImportBatch extends Model
{
    protected $table = 'wordpress_import_batches';

    protected $fillable = [
        'batch_code',
        'source_path',
        'source_format',
        'is_dry_run',
        'status',
        'total_items',
        'imported_count',
        'skipped_count',
        'review_flagged_count',
        'redirects_created_count',
        'report_summary',
        'rolled_back_at',
    ];

    protected function casts(): array
    {
        return [
            'is_dry_run' => 'boolean',
            'total_items' => 'integer',
            'imported_count' => 'integer',
            'skipped_count' => 'integer',
            'review_flagged_count' => 'integer',
            'redirects_created_count' => 'integer',
            'report_summary' => 'array',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(WordPressImportItem::class, 'batch_id');
    }
}
