<?php

namespace App\Modules\Cms\Support;

final class ThemePresets
{
    /**
     * @return array<string, array{label: string, primary_bg: string, accent_bg: string, hero_class: string, announcement_bg: string}>
     */
    public static function colorPresets(): array
    {
        return [
            'karakoram_emerald' => [
                'label' => 'Karakoram Emerald (Default)',
                'primary_bg' => 'bg-emerald-800',
                'accent_bg' => 'bg-amber-600',
                'hero_class' => 'pp-hero-pattern',
                'announcement_bg' => 'bg-emerald-950 text-emerald-50',
            ],
            'himalayan_slate' => [
                'label' => 'Himalayan Slate & Gold',
                'primary_bg' => 'bg-slate-900',
                'accent_bg' => 'bg-amber-600',
                'hero_class' => 'bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950',
                'announcement_bg' => 'bg-slate-950 text-slate-100',
            ],
            'indus_heritage' => [
                'label' => 'Indus Terracotta & Forest',
                'primary_bg' => 'bg-emerald-900',
                'accent_bg' => 'bg-amber-700',
                'hero_class' => 'bg-gradient-to-br from-stone-950 via-emerald-950 to-amber-950',
                'announcement_bg' => 'bg-stone-900 text-amber-100',
            ],
            'glacier_minimal' => [
                'label' => 'Glacier Crisp Slate',
                'primary_bg' => 'bg-teal-800',
                'accent_bg' => 'bg-amber-600',
                'hero_class' => 'bg-gradient-to-br from-teal-950 via-slate-900 to-slate-950',
                'announcement_bg' => 'bg-teal-950 text-teal-50',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, body_class: string}>
     */
    public static function typographyPresets(): array
    {
        return [
            'modern_editorial' => [
                'label' => 'Modern Editorial Sans',
                'body_class' => 'font-sans',
            ],
            'classic_serif_headings' => [
                'label' => 'Classic Heritage Serif Accents',
                'body_class' => 'font-sans pp-serif-headings',
            ],
            'compact_clean' => [
                'label' => 'Compact Utility Sans',
                'body_class' => 'font-sans tracking-normal',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sectionStylePresets(): array
    {
        return [
            'default' => 'Glacier Canvas',
            'surface' => 'White Surface',
            'emerald' => 'Soft Emerald Tint',
            'dark' => 'Alpine Dark',
            'accent' => 'Warm Saffron Tint',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function deviceVisibilityOptions(): array
    {
        return [
            'all' => 'All Devices',
            'desktop_only' => 'Desktop & Tablet Only',
            'mobile_only' => 'Mobile Only',
        ];
    }

    public static function deviceVisibilityClass(string $visibility): string
    {
        return match ($visibility) {
            'desktop_only' => 'hidden md:block',
            'mobile_only' => 'block md:hidden',
            default => 'block',
        };
    }

    public static function sectionWrapperClass(string $stylePreset): string
    {
        return match ($stylePreset) {
            'surface' => 'bg-white text-slate-900 border-y border-slate-200/80',
            'emerald' => 'bg-emerald-50/70 text-slate-900 border-y border-emerald-100',
            'dark' => 'bg-slate-950 text-white',
            'accent' => 'bg-amber-50/70 text-slate-900 border-y border-amber-200/70',
            default => 'bg-slate-50 text-slate-900',
        };
    }

    /**
     * @return array{label: string, primary_bg: string, accent_bg: string, hero_class: string, announcement_bg: string}
     */
    public static function resolveColorPreset(?string $key): array
    {
        $presets = self::colorPresets();

        return $presets[$key ?? ''] ?? $presets['karakoram_emerald'];
    }

    /**
     * @return array{label: string, body_class: string}
     */
    public static function resolveTypographyPreset(?string $key): array
    {
        $presets = self::typographyPresets();

        return $presets[$key ?? ''] ?? $presets['modern_editorial'];
    }
}
