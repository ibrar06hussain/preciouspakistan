<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsMenu extends Model
{
    public const LOCATION_HEADER = 'header';

    public const LOCATION_MOBILE = 'mobile';

    public const LOCATION_FOOTER_PRIMARY = 'footer_primary';

    public const LOCATION_FOOTER_LEGAL = 'footer_legal';

    protected $fillable = [
        'name',
        'location',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'menu_id')->orderBy('sort_order')->orderBy('id');
    }
}
