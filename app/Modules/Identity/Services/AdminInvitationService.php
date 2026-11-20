<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Identity\Models\AdminInvitation;
use App\Modules\Identity\Notifications\AdminInvitationNotification;
use App\Modules\Rbac\Services\AuditLogger;
use App\Modules\Rbac\Services\PrivilegeGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

final class AdminInvitationService
{
    public function __construct(
        private readonly PrivilegeGuard $guard,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{invitation: AdminInvitation, plain_token: string}
     *
     * @throws AuthorizationException
     */
    public function createInvitation(User $inviter, string $name, string $email, string $roleName): array
    {
        $email = strtolower(trim($email));
        $name = trim($name);
        $roleName = trim($roleName);

        $this->guard->assertValidRoleName($roleName);

        if (! $inviter->hasRole('Super Admin') && ! $inviter->can('users.invite')) {
            throw new AuthorizationException('You do not have permission to invite staff users.');
        }

        $this->guard->assertCanInviteRole($inviter, $roleName);

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

            $invitation = AdminInvitation::query()->create([
                'name' => $name,
                'email' => $email,
                'role_name' => $roleName,
                'token_hash' => $tokenHash,
                'invited_by' => $inviter->id,
                'expires_at' => $expiresAt,
            ]);

            $this->audit->record(
                actor: $inviter,
                event: 'invitation.created',
                summary: "Invited {$email} as {$roleName}",
                auditable: $invitation,
                targetUser: null,
                beforeState: null,
                afterState: [
                    'email' => $email,
                    'role_name' => $roleName,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            );

            return $invitation;
        });

        Notification::route('mail', $email)->notify(
            new AdminInvitationNotification($invitation, $plainToken)
        );

        return [
            'invitation' => $invitation,
            'plain_token' => $plainToken,
        ];
    }

    /**
     * @throws AuthorizationException
     */
    public function revokeInvitation(User $actor, AdminInvitation $invitation): AdminInvitation
    {
        if (! $actor->hasRole('Super Admin') && ! $actor->can('users.invite')) {
            throw new AuthorizationException('You do not have permission to revoke staff invitations.');
        }

        return DB::transaction(function () use ($actor, $invitation): AdminInvitation {
            $locked = AdminInvitation::query()->lockForUpdate()->findOrFail($invitation->id);

            if (! $locked->isUsable()) {
                throw new RuntimeException('This invitation can no longer be revoked.');
            }

            $locked->forceFill(['revoked_at' => now()])->save();

            $this->audit->record(
                actor: $actor,
                event: 'invitation.revoked',
                summary: "Revoked staff invitation for {$locked->email}",
                auditable: $locked,
                targetUser: null,
                beforeState: ['status' => 'pending', 'role_name' => $locked->role_name],
                afterState: ['status' => 'revoked', 'revoked_at' => $locked->revoked_at?->toIso8601String()],
            );

            return $locked;
        });
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

            $this->audit->record(
                actor: $user,
                event: 'invitation.accepted',
                summary: "Accepted staff invitation as {$invitation->role_name}",
                auditable: $invitation,
                targetUser: $user,
                beforeState: ['status' => 'pending'],
                afterState: [
                    'status' => 'accepted',
                    'user_id' => $user->id,
                    'role_name' => $invitation->role_name,
                ],
            );

            return $user->fresh('roles');
        });
    }
}
