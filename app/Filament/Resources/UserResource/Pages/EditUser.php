<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Modules\Rbac\Services\UserManagementService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Permissions Preview'),
            DeleteAction::make()
                ->using(function (User $record): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    app(UserManagementService::class)->deleteUser($actor, $record);
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();
        $breakdown = app(UserManagementService::class)->permissionBreakdown($record);

        $data['role_names'] = $breakdown['roles'];
        $data['direct_permission_names'] = $breakdown['direct'];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var User $target */
        $target = $record;

        $payload = [
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'] ?? null,
        ];

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if ($actor->can('roles.assign')) {
            $payload['roles'] = $data['role_names'] ?? [];
            $payload['direct_permissions'] = $data['direct_permission_names'] ?? [];
        }

        return app(UserManagementService::class)->updateUser($actor, $target, $payload);
    }
}
