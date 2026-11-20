<?php

namespace App\Filament\Resources\CmsMenuResource\Pages;

use App\Filament\Resources\CmsMenuResource;
use App\Models\User;
use App\Modules\Cms\Services\MenuService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCmsMenu extends CreateRecord
{
    protected static string $resource = CmsMenuResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(MenuService::class)->saveMenu(
            actor: $actor,
            existing: null,
            name: (string) $data['name'],
            location: (string) $data['location'],
            isActive: (bool) ($data['is_active'] ?? true),
            items: $data['menu_items'] ?? [],
        );
    }
}
