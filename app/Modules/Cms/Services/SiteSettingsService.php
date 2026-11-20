<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

final class SiteSettingsService
{
    public const CACHE_KEY = 'precious.cms.public_site_settings';

    /**
     * @return array<string, mixed>
     */
    public function allPublic(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): array {
            if (! Schema::hasTable('site_settings')) {
                return [];
            }

            $settings = [];

            foreach (SiteSetting::query()->where('is_public', true)->get() as $record) {
                $settings[$record->key] = $record->decodedValue();
            }

            return $settings;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->allPublic();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function set(string $key, mixed $value, string $group = 'general', string $valueType = 'string', bool $isPublic = true): SiteSetting
    {
        $encoded = match ($valueType) {
            'json' => json_encode($value, JSON_THROW_ON_ERROR),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        $setting = SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $encoded,
                'value_type' => $valueType,
                'is_public' => $isPublic,
            ]
        );

        $this->flushCache();

        return $setting;
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
