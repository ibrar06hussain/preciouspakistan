<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Identity\Models\AdminInvitation;
use App\Modules\Identity\Notifications\AdminInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Role;

final class AdminInvitationService
{
    /**
     * @return array{invitation: AdminInvitation, plain_token: string}
     */
    public function createInvitation(User $inviter, string $name, string $email, string $roleName): array
    {
        $email = strtolower(trim($email));
        $name = trim($name);
        $roleName = trim($roleName);

        if ($roleName === '' || strcasecmp($roleName, 'Customer') === 0 || strcasecmp($roleName, 'Hotel Partner') === 0) {
            throw new InvalidArgumentException('Invalid staff role selected for admin invitation.');
        }

        if (User::query()->where('email', $email)->exists()) {
            throw new RuntimeException('A user with this email address already exists.');
        }

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = now()->addHours((int) config('precious.invitations.expires_in_hours', 72));

        $invitation = DB::transaction(function () use ($inviter, $name, $email, $roleName, $tokenHash, $expiresAt): AdminInvitation {
            AdminInvitation::query()
                ->where('email', $email)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return AdminInvitation::query()->create([
                'name' => $name,
                'email' => $email,
                'role_name' => $roleName,
                'token_hash' => $tokenHash,
                'invited_by' => $inviter->id,
                'expires_at' => $expiresAt,
            ]);
        });

        Notification::route('mail', $email)->notify(
            new AdminInvitationNotification($invitation, $plainToken)
        );

        return [
            'invitation' => $invitation,
            'plain_token' => $plainToken,
        ];
    }

    public function findUsableByPlainToken(string $plainToken): ?AdminInvitation
    {
        $invitation = AdminInvitation::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if ($invitation === null || ! $invitation->isUsable()) {
            return null;
        }

        return $invitation;
    }

    public function acceptInvitation(string $plainToken, string $password, ?string $phone = null): User
    {
        return DB::transaction(function () use ($plainToken, $password, $phone): User {
            $invitation = AdminInvitation::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->lockForUpdate()
                ->first();

            if ($invitation === null || ! $invitation->isUsable()) {
                throw new RuntimeException('This invitation link is invalid, expired, or has already been used.');
            }

            if (User::query()->where('email', $invitation->email)->exists()) {
                throw new RuntimeException('An account with this email address already exists.');
            }

            $role = Role::findOrCreate($invitation->role_name, 'web');

            $user = User::query()->create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'phone' => $phone,
                'password' => $password,
                'account_type' => AccountType::Staff,
                'is_active' => true,
                'email_verified_at' => now(),
                'invited_by' => $invitation->invited_by,
            ]);

            $user->assignRole($role);

            $invitation->forceFill([
                'accepted_at' => now(),
            ])->save();

            return $user->fresh('roles');
        });
    }
}
