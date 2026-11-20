<?php

namespace App\Modules\Nomads\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NomadBlackout extends Model
{
    protected $fillable = [
        'nomad_location_id',
        'start_date',
        'end_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(NomadLocation::class, 'nomad_location_id');
    }
}
