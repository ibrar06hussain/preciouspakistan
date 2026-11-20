<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Models\CmsPageVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

final class PageRendererService
{
    public const HOME_CACHE_KEY = 'precious.cms.rendered.home';

    public const SLUG_CACHE_PREFIX = 'precious.cms.rendered.slug.';

    /**
     * @return array{page: CmsPage, version: CmsPageVersion}|null
     */
    public function resolvePublishedHomepage(): ?array
    {
        return Cache::remember(self::HOME_CACHE_KEY, now()->addMinutes(15), function (): ?array {
            if (! Schema::hasTable('cms_pages')) {
                return null;
            }

            $page = CmsPage::query()
                ->where('is_homepage', true)
                ->where('status', CmsPage::STATUS_PUBLISHED)
                ->whereNotNull('published_version_id')
                ->with(['publishedVersion.sections'])
                ->first();

            if ($page === null || $page->publishedVersion === null) {
                return null;
            }

            return [
                'page' => $page,
                'version' => $page->publishedVersion,
            ];
        });
    }

    /**
     * @return array{page: CmsPage, version: CmsPageVersion}|null
     */
    public function resolvePublishedBySlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));

        return Cache::remember(self::SLUG_CACHE_PREFIX.$slug, now()->addMinutes(15), function () use ($slug): ?array {
            if (! Schema::hasTable('cms_pages')) {
                return null;
            }

            $page = CmsPage::query()
                ->where('slug', $slug)
                ->where('status', CmsPage::STATUS_PUBLISHED)
                ->whereNotNull('published_version_id')
                ->with(['publishedVersion.sections'])
                ->first();

            if ($page === null || $page->publishedVersion === null) {
                return null;
            }

            return [
                'page' => $page,
                'version' => $page->publishedVersion,
            ];
        });
    }

    /**
     * Uncached draft preview loader (strictly for authenticated CMS staff).
     *
     * @return array{page: CmsPage, version: CmsPageVersion}|null
     */
    public function resolveDraftForPreview(CmsPage $page): ?array
    {
        $page->load(['draftVersion.sections', 'publishedVersion.sections']);
        $version = $page->draftVersion ?? $page->publishedVersion;

        if ($version === null) {
            return null;
        }

        return [
            'page' => $page,
            'version' => $version,
        ];
    }

    public function invalidatePage(CmsPage $page): void
    {
        Cache::forget(self::SLUG_CACHE_PREFIX.strtolower($page->slug));
        if ($page->is_homepage) {
            Cache::forget(self::HOME_CACHE_KEY);
        }
    }

    public function invalidateSlug(string $slug): void
    {
        Cache::forget(self::SLUG_CACHE_PREFIX.strtolower($slug));
    }
}
