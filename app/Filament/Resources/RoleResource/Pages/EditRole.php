<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\User;
use App\Modules\Rbac\Services\RoleManagementService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function (Role $record): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    app(RoleManagementService::class)->deleteRole($actor, $record);
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $record */
        $record = $this->getRecord();
        $data['permission_names'] = $record->permissions()->pluck('name')->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var Role $role */
        $role = $record;

        return app(RoleManagementService::class)->updateRole(
            actor: $actor,
            role: $role,
            name: (string) ($data['name'] ?? $role->name),
            permissionNames: $data['permission_names'] ?? [],
        );
    }
}
