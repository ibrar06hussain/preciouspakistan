<?php

namespace App\Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    public const PLATFORMS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'whatsapp' => 'WhatsApp',
    ];

    protected $fillable = [
        'platform',
        'label',
        'url',
        'whatsapp_number',
        'sort_order',
        'is_enabled',
        'placements',
        'open_in_new_tab',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_enabled' => 'boolean',
            'placements' => 'array',
            'open_in_new_tab' => 'boolean',
        ];
    }
}
