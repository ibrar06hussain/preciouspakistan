<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'value_type',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function decodedValue(): mixed
    {
        return match ($this->value_type) {
            'json' => json_decode((string) $this->value, true),
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            default => $this->value,
        };
    }
}
