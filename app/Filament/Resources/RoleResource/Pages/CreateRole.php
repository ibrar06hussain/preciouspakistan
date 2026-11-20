<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\User;
use App\Modules\Rbac\Services\RoleManagementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(RoleManagementService::class)->createRole(
            actor: $actor,
            name: (string) $data['name'],
            permissionNames: $data['permission_names'] ?? [],
        );
    }
}
