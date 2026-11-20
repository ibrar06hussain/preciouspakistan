<?php

namespace App\Modules\Cms\Services;

use App\Models\User;
use App\Modules\Cms\Models\CmsMenu;
use App\Modules\Cms\Models\CmsMenuItem;
use App\Modules\Cms\Support\HtmlSanitizerService;
use App\Modules\Rbac\Services\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MenuService
{
    public const CACHE_PREFIX = 'precious.cms.menu.';

    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return list<array{label: string, url: string, target: string, device_visibility: string}>
     */
    public function getItemsForLocation(string $location): array
    {
        return Cache::remember(self::CACHE_PREFIX.$location, now()->addMinutes(15), function () use ($location): array {
            if (! Schema::hasTable('cms_menus') || ! Schema::hasTable('cms_menu_items')) {
                return [];
            }

            $menu = CmsMenu::query()
                ->where('location', $location)
                ->where('is_active', true)
                ->with(['items' => fn ($q) => $q->where('is_visible', true)->orderBy('sort_order')->orderBy('id')])
                ->first();

            if ($menu === null) {
                return [];
            }

            return $menu->items->map(fn (CmsMenuItem $item): array => [
                'label' => $item->label,
                'url' => $item->url,
                'target' => in_array($item->target, ['_self', '_blank'], true) ? $item->target : '_self',
                'device_visibility' => $item->device_visibility,
            ])->values()->all();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     *
     * @throws AuthorizationException
     */
    public function saveMenu(User $actor, ?CmsMenu $existing, string $name, string $location, bool $isActive, array $items): CmsMenu
    {
        if (! $actor->can('cms.update')) {
            throw new AuthorizationException('You do not have permission to manage navigation menus.');
        }

        $cleanName = $this->sanitizer->sanitizePlainText($name, 'menu.name', 120);
        $cleanLocation = trim($location);

        $sanitizedItems = [];
        foreach (array_values($items) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = $this->sanitizer->sanitizePlainText($item['label'] ?? null, 'menu_item.label', 120);
            $url = $this->sanitizer->sanitizeUrl($item['url'] ?? null, 'menu_item.url');
            if ($label === '' || $url === '') {
                continue;
            }
            $sanitizedItems[] = [
                'label' => $label,
                'url' => $url,
                'target' => in_array($item['target'] ?? '_self', ['_self', '_blank'], true) ? ($item['target'] ?? '_self') : '_self',
                'sort_order' => $index,
                'is_visible' => (bool) ($item['is_visible'] ?? true),
                'device_visibility' => in_array($item['device_visibility'] ?? 'all', ['all', 'desktop_only', 'mobile_only'], true)
                    ? ($item['device_visibility'] ?? 'all')
                    : 'all',
            ];
        }

        $menu = DB::transaction(function () use ($actor, $existing, $cleanName, $cleanLocation, $isActive, $sanitizedItems): CmsMenu {
            $menu = $existing ?? new CmsMenu;
            $oldLocation = $menu->exists ? $menu->location : null;

            $menu->fill([
                'name' => $cleanName,
                'location' => $cleanLocation,
                'is_active' => $isActive,
            ])->save();

            $menu->items()->delete();

            foreach ($sanitizedItems as $itemData) {
                $menu->items()->create($itemData);
            }

            $this->audit->record(
                actor: $actor,
                event: 'cms.menu_saved',
                summary: "Saved CMS menu [{$menu->location}] ({$menu->name})",
                auditable: $menu,
                targetUser: null,
                beforeState: $oldLocation ? ['location' => $oldLocation] : null,
                afterState: ['location' => $menu->location, 'items_count' => count($sanitizedItems)],
            );

            if ($oldLocation !== null && $oldLocation !== $menu->location) {
                $this->flushLocation($oldLocation);
            }

            return $menu->fresh('items');
        });

        $this->flushLocation($menu->location);

        return $menu;
    }

    public function flushLocation(string $location): void
    {
        Cache::forget(self::CACHE_PREFIX.$location);
    }

    public function flushAll(): void
    {
        foreach ([
            CmsMenu::LOCATION_HEADER,
            CmsMenu::LOCATION_MOBILE,
            CmsMenu::LOCATION_FOOTER_PRIMARY,
            CmsMenu::LOCATION_FOOTER_LEGAL,
        ] as $location) {
            $this->flushLocation($location);
        }
    }
}
