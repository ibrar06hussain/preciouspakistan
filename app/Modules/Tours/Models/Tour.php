<?php

namespace App\Modules\Tours\Models;

use App\Models\User;
use App\Modules\Destinations\Models\Destination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tour extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const PRICING_FIXED = 'fixed';

    public const PRICING_PER_PERSON = 'per_person';

    public const PRICING_CUSTOM_QUOTE = 'custom_quote';

    public const CATEGORIES = [
        'sightseeing' => 'Sightseeing',
        'trekking' => 'Trekking & Adventure',
        'culture' => 'Culture & Heritage',
        'honeymoon' => 'Honeymoon & Couples',
        'group' => 'Group Expedition',
        'custom' => 'Custom Private Tour',
    ];

    public const DIFFICULTIES = [
        'easy' => 'Easy',
        'moderate' => 'Moderate',
        'challenging' => 'Challenging',
        'expedition' => 'High-Altitude Expedition',
    ];

    public const PRICING_MODELS = [
        self::PRICING_PER_PERSON => 'Per Person',
        self::PRICING_FIXED => 'Fixed Package Rate',
        self::PRICING_CUSTOM_QUOTE => 'Custom Quotation (Request Quote)',
    ];

    protected $fillable = [
        'code',
        'title',
        'slug',
        'category',
        'status',
        'published_at',
        'duration_days',
        'duration_nights',
        'min_guests',
        'max_guests',
        'booking_lead_days',
        'booking_window_days',
        'difficulty',
        'languages',
        'pickup_location',
        'dropoff_location',
        'summary',
        'description_html',
        'highlights',
        'inclusions',
        'exclusions',
        'cancellation_policy_html',
        'faqs',
        'hero_image_url',
        'gallery',
        'pricing_model',
        'base_price_pkr',
        'base_price_usd',
        'includes_base_accommodation',
        'requires_company_vehicle',
        'supports_hotel_selection',
        'supports_nomad_addon',
        'is_featured',
        'is_sample_content',
        'seo_title',
        'seo_description',
        'canonical_url',
        'og_image_url',
        'noindex',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'duration_days' => 'integer',
            'duration_nights' => 'integer',
            'min_guests' => 'integer',
            'max_guests' => 'integer',
            'booking_lead_days' => 'integer',
            'booking_window_days' => 'integer',
            'languages' => 'array',
            'highlights' => 'array',
            'inclusions' => 'array',
            'exclusions' => 'array',
            'faqs' => 'array',
            'gallery' => 'array',
            'base_price_pkr' => 'decimal:2',
            'base_price_usd' => 'decimal:2',
            'includes_base_accommodation' => 'boolean',
            'requires_company_vehicle' => 'boolean',
            'supports_hotel_selection' => 'boolean',
            'supports_nomad_addon' => 'boolean',
            'is_featured' => 'boolean',
            'is_sample_content' => 'boolean',
            'noindex' => 'boolean',
        ];
    }

    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class, 'tour_destinations', 'tour_id', 'destination_id')
            ->withPivot(['sort_order', 'is_primary'])
            ->orderBy('tour_destinations.sort_order');
    }

    public function itineraryDays(): HasMany
    {
        return $this->hasMany(TourItineraryDay::class, 'tour_id')->orderBy('day_number');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(TourStay::class, 'tour_id')->orderBy('stay_order')->orderBy('check_in_day');
    }

    public function departures(): HasMany
    {
        return $this->hasMany(TourDeparture::class, 'tour_id')->orderBy('start_date');
    }

    public function openDepartures(): HasMany
    {
        return $this->departures()
            ->where('status', TourDeparture::STATUS_OPEN)
            ->whereDate('start_date', '>=', now()->toDateString());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function publicUrl(): string
    {
        return route('tours.show', ['slug' => $this->slug]);
    }

    /**
     * Format authoritative starting price or strictly return "Request Quote" when unknown/custom.
     * NEVER displays $0 or PKR 0.
     */
    public function displayPrice(string $currency = 'PKR'): string
    {
        if ($this->pricing_model === self::PRICING_CUSTOM_QUOTE) {
            return 'Request Quote';
        }

        $currency = strtoupper($currency);
        $raw = $currency === 'USD' ? $this->base_price_usd : $this->base_price_pkr;

        if ($raw === null || bccomp((string) $raw, '0.00', 2) <= 0) {
            // Fall back to alternate currency if available; otherwise Request Quote.
            $fallbackCurrency = $currency === 'USD' ? 'PKR' : 'USD';
            $fallback = $fallbackCurrency === 'USD' ? $this->base_price_usd : $this->base_price_pkr;

            if ($fallback === null || bccomp((string) $fallback, '0.00', 2) <= 0) {
                return 'Request Quote';
            }

            $currency = $fallbackCurrency;
            $raw = $fallback;
        }

        $formatted = $currency === 'USD'
            ? 'USD '.number_format((float) $raw, 2)
            : 'PKR '.number_format((float) $raw, 0);

        $unit = $this->pricing_model === self::PRICING_PER_PERSON ? ' / person' : ' / package';

        return 'From '.$formatted.$unit;
    }
}
