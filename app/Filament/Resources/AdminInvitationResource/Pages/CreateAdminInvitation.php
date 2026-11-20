<?php

namespace App\Filament\Resources\AdminInvitationResource\Pages;

use App\Filament\Resources\AdminInvitationResource;
use App\Models\User;
use App\Modules\Identity\Services\AdminInvitationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdminInvitation extends CreateRecord
{
    protected static string $resource = AdminInvitationResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        $result = app(AdminInvitationService::class)->createInvitation(
            inviter: $actor,
            name: (string) $data['name'],
            email: (string) $data['email'],
            roleName: (string) $data['role_name'],
        );

        return $result['invitation'];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
