<?php

namespace App\Modules\Destinations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UrlRedirect extends Model
{
    protected $fillable = [
        'source_path',
        'target_path',
        'status_code',
        'reason',
        'hits',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'hits' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
