<?php

namespace App\Filament\Resources\CmsMenuResource\Pages;

use App\Filament\Resources\CmsMenuResource;
use App\Models\User;
use App\Modules\Cms\Models\CmsMenu;
use App\Modules\Cms\Services\MenuService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCmsMenu extends EditRecord
{
    protected static string $resource = CmsMenuResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var CmsMenu $menu */
        $menu = $this->getRecord();
        $data['menu_items'] = $menu->items()->get()->map(fn ($item): array => [
            'label' => $item->label,
            'url' => $item->url,
            'target' => $item->target,
            'device_visibility' => $item->device_visibility,
            'is_visible' => (bool) $item->is_visible,
        ])->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var CmsMenu $menu */
        $menu = $record;

        return app(MenuService::class)->saveMenu(
            actor: $actor,
            existing: $menu,
            name: (string) $data['name'],
            location: (string) $data['location'],
            isActive: (bool) ($data['is_active'] ?? true),
            items: $data['menu_items'] ?? [],
        );
    }
}
