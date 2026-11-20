<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Timezone & Regional Defaults
    |--------------------------------------------------------------------------
    |
    | All database timestamps are stored in UTC. Human-facing dates and times
    | are formatted in Asia/Karachi (PKT) unless explicitly specified.
    |
    */
    'storage_timezone' => 'UTC',
    'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Asia/Karachi'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    */
    'currencies' => [
        'default' => 'PKR',
        'supported' => ['PKR', 'USD'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads Configuration
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'public_disk' => env('UPLOADS_DISK', 'uploads'),
        'private_disk' => env('PRIVATE_UPLOADS_DISK', 'private_uploads'),
        'max_image_kb' => (int) env('PRECIOUS_MAX_IMAGE_KB', 8192),
        'allowed_image_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/avif'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional MFA Hook Configuration
    |--------------------------------------------------------------------------
    */
    'mfa' => [
        'enabled' => (bool) env('PRECIOUS_MFA_ENABLED', false),
        'driver' => env('PRECIOUS_MFA_DRIVER', 'null'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Invitation Settings
    |--------------------------------------------------------------------------
    */
    'invitations' => [
        'expires_in_hours' => 72,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Defaults
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'login_per_minute' => 5,
        'register_per_minute' => 5,
        'password_reset_per_minute' => 5,
        'api_per_minute' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Digital Nomad & Booking Policy Settings
    |--------------------------------------------------------------------------
    */
    'nomads' => [
        'allow_standalone_booking' => (bool) env('PRECIOUS_NOMADS_ALLOW_STANDALONE', false),
    ],

    'booking' => [
        'quote_expiry_minutes' => (int) env('PRECIOUS_QUOTE_EXPIRY_MINUTES', 60),
        'vehicle_hold_minutes' => (int) env('PRECIOUS_VEHICLE_HOLD_MINUTES', 45),
        'vehicle_rental_cutoff_hour_pkt' => 18,
        'vehicle_grace_period_minutes' => 60,
        'default_tax_rate_percent' => (string) env('PRECIOUS_DEFAULT_TAX_PERCENT', '0.00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Brand Defaults (Used only when DB site_settings is unseeded)
    |--------------------------------------------------------------------------
    */
    'fallback_brand' => [
        'name' => 'Precious Pakistan',
        'tagline' => 'Curated Journeys Across Every Province & Peak of Pakistan',
        'support_email' => 'bookings@preciouspakistan.com',
        'operating_hours' => 'Mon–Sat, 09:00–19:00 PKT (Asia/Karachi)',
    ],

];
