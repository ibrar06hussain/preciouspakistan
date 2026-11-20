<?php

namespace App\Filament\Pages;

use App\Modules\Seo\Services\SeoService;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class SeoAuditPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Content & SEO';

    protected static ?int $navigationSort = 45;

    protected static ?string $navigationLabel = 'SEO Audit & Sitemap';

    protected static ?string $title = 'Tourism SEO Audit & Sitemap Health';

    protected string $view = 'filament.pages.seo-audit';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('seo.view');
    }

    /**
     * @return array{audit: array<string, mixed>, sitemap_count: int}
     */
    protected function getViewData(): array
    {
        $seo = app(SeoService::class);

        return [
            'audit' => $seo->audit(),
            'sitemap_count' => count($seo->sitemapEntries()),
        ];
    }
}
