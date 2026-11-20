<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Modules\Rbac\Services\UserManagementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        return app(UserManagementService::class)->createUser($actor, [
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => (string) $data['password'],
            'account_type' => $data['account_type'] ?? 'staff',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'roles' => $data['role_names'] ?? [],
            'direct_permissions' => $data['direct_permission_names'] ?? [],
        ]);
    }
}
