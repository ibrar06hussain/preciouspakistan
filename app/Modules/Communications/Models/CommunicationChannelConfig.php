<?php

namespace App\Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationChannelConfig extends Model
{
    protected $fillable = [
        'channel',
        'display_name',
        'is_enabled',
        'status',
        'encrypted_credentials',
        'webhook_verify_token_hash',
        'opt_in_required',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'encrypted_credentials' => 'encrypted:array',
            'opt_in_required' => 'boolean',
            'last_verified_at' => 'datetime',
        ];
    }
}
