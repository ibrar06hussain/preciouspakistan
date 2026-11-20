<?php

namespace App\Modules\Hotels\Models;

use App\Models\User;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\District;
use App\Modules\Destinations\Models\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'slug',
        'region_id',
        'district_id',
        'destination_id',
        'address',
        'latitude',
        'longitude',
        'short_description_html',
        'main_photo_url',
        'gallery',
        'public_contact_phone',
        'public_contact_email',
        'status',
        'is_sample_content',
        'seo_title',
        'seo_description',
        'canonical_url',
        'noindex',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'gallery' => 'array',
            'is_sample_content' => 'boolean',
            'noindex' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(HotelRoomType::class, 'hotel_id')->orderBy('name');
    }

    public function tourStayLinks(): HasMany
    {
        return $this->hasMany(TourStayHotel::class, 'hotel_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publicInfoUrl(): string
    {
        return route('hotels.show', ['slug' => $this->slug]);
    }
}
