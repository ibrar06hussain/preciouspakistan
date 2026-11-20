<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FxRateSnapshot extends Model
{
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'source',
        'is_manual_override',
        'notes',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'is_manual_override' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
