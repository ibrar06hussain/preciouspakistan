<?php

namespace App\Modules\Rbac\Services;

use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Rbac\Support\RbacCatalog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

final class UserManagementService
{
    public function __construct(
        private readonly PrivilegeGuard $guard,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, password: string, account_type?: string|AccountType, is_active?: bool, verify_email?: bool, roles?: list<string>, direct_permissions?: list<string>}  $data
     *
     * @throws AuthorizationException
     */
    public function createUser(User $actor, array $data): User
    {
        if (! $actor->can('users.create')) {
            throw new AuthorizationException('You do not have permission to create users.');
        }

        $accountType = $data['account_type'] ?? AccountType::Staff;
        if (is_string($accountType)) {
            $accountType = AccountType::from($accountType);
        }

        $roles = array_values(array_unique(array_filter(array_map('trim', $data['roles'] ?? []))));
        $directPermissions = array_values(array_unique(array_filter(array_map('trim', $data['direct_permissions'] ?? []))));

        if ($accountType === AccountType::Customer && $roles === []) {
            $roles = [RbacCatalog::ROLE_CUSTOMER];
        }

        if (($roles !== [] || $directPermissions !== []) && ! $actor->can('roles.assign')) {
            throw new AuthorizationException('You do not have permission to assign roles or permissions.');
        }

        return DB::transaction(function () use ($actor, $data, $accountType, $roles, $directPermissions): User {
            $user = User::query()->create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'account_type' => $accountType,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'email_verified_at' => ($data['verify_email'] ?? ($accountType === AccountType::Staff)) ? now() : null,
                'invited_by' => $actor->id,
            ]);

            if ($roles !== []) {
                $this->guard->assertCanAssignRoles($actor, $user, $roles);
                foreach ($roles as $roleName) {
                    Role::findOrCreate($roleName, 'web');
                }
                $user->syncRoles($roles);
            }

            if ($directPermissions !== []) {
                $this->guard->assertCanAssignDirectPermissions($actor, $user, $directPermissions);
                $user->syncPermissions($directPermissions);
            }

            $user->refresh()->load(['roles', 'permissions']);

            $this->audit->record(
                actor: $actor,
                event: 'user.created',
                summary: "Created {$accountType->label()} user {$user->email}",
                auditable: $user,
                targetUser: $user,
                beforeState: null,
                afterState: $this->snapshotUser($user),
            );

            return $user;
        });
    }

    /**
     * @param  array{name?: string, email?: string, phone?: ?string, password?: ?string, is_active?: bool, roles?: list<string>, direct_permissions?: list<string>}  $data
     *
     * @throws AuthorizationException
     */
    public function updateUser(User $actor, User $target, array $data): User
    {
        if (! $actor->can('users.update')) {
            throw new AuthorizationException('You do not have permission to update users.');
        }

        $this->guard->assertCanManageTargetUser($actor, $target);

        return DB::transaction(function () use ($actor, $target, $data): User {
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->id);
            $before = $this->snapshotUser($lockedTarget);

            if (array_key_exists('is_active', $data) && $lockedTarget->is_active !== (bool) $data['is_active']) {
                if (! $actor->can('users.deactivate')) {
                    throw new AuthorizationException('You do not have permission to activate or deactivate users.');
                }

                if (! (bool) $data['is_active']) {
                    $this->guard->assertNotLastActiveSuperAdmin($lockedTarget);
                }
            }

            $updates = [];
            if (array_key_exists('name', $data)) {
                $updates['name'] = trim((string) $data['name']);
            }
            if (array_key_exists('email', $data)) {
                $updates['email'] = strtolower(trim((string) $data['email']));
            }
            if (array_key_exists('phone', $data)) {
                $updates['phone'] = $data['phone'];
            }
            if (filled($data['password'] ?? null)) {
                $updates['password'] = $data['password'];
            }
            if (array_key_exists('is_active', $data)) {
                $updates['is_active'] = (bool) $data['is_active'];
            }

            if ($updates !== []) {
                $lockedTarget->fill($updates)->save();
            }

            $rolesChanged = false;
            $permissionsChanged = false;

            if (array_key_exists('roles', $data)) {
                $requestedRoles = array_values(array_unique(array_filter(array_map('trim', $data['roles']))));
                sort($requestedRoles);
                $currentRoles = $before['roles'];

                if ($requestedRoles !== $currentRoles) {
                    if (! $actor->can('roles.assign')) {
                        throw new AuthorizationException('You do not have permission to modify assigned roles.');
                    }

                    $this->guard->assertCanAssignRoles($actor, $lockedTarget, $requestedRoles);
                    $lockedTarget->syncRoles($requestedRoles);
                    $rolesChanged = true;
                }
            }

            if (array_key_exists('direct_permissions', $data)) {
                $requestedPermissions = array_values(array_unique(array_filter(array_map('trim', $data['direct_permissions']))));
                sort($requestedPermissions);
                $currentPermissions = $before['direct_permissions'];

                if ($requestedPermissions !== $currentPermissions) {
                    if (! $actor->can('roles.assign')) {
                        throw new AuthorizationException('You do not have permission to modify direct permissions.');
                    }

                    $this->guard->assertCanAssignDirectPermissions($actor, $lockedTarget, $requestedPermissions);
                    $lockedTarget->syncPermissions($requestedPermissions);
                    $permissionsChanged = true;
                }
            }

            $lockedTarget->refresh()->load(['roles', 'permissions']);
            $after = $this->snapshotUser($lockedTarget);

            $event = match (true) {
                $rolesChanged || $permissionsChanged => 'user.permissions_updated',
                $before['is_active'] !== $after['is_active'] => $after['is_active'] ? 'user.activated' : 'user.deactivated',
                default => 'user.updated',
            };

            if ($before !== $after || filled($data['password'] ?? null)) {
                $this->audit->record(
                    actor: $actor,
                    event: $event,
                    summary: "Updated user {$lockedTarget->email} ({$event})",
                    auditable: $lockedTarget,
                    targetUser: $lockedTarget,
                    beforeState: $before,
                    afterState: $after,
                );
            }

            return $lockedTarget;
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function setActiveStatus(User $actor, User $target, bool $active): User
    {
        if (! $actor->can('users.deactivate')) {
            throw new AuthorizationException('You do not have permission to change account activation status.');
        }

        $this->guard->assertCanManageTargetUser($actor, $target);

        return DB::transaction(function () use ($actor, $target, $active): User {
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->id);

            if (! $active) {
                $this->guard->assertNotLastActiveSuperAdmin($lockedTarget);
            }

            $before = $this->snapshotUser($lockedTarget);

            $lockedTarget->forceFill(['is_active' => $active])->save();
            $after = $this->snapshotUser($lockedTarget->fresh(['roles', 'permissions']));

            $event = $active ? 'user.activated' : 'user.deactivated';

            $this->audit->record(
                actor: $actor,
                event: $event,
                summary: ($active ? 'Activated' : 'Deactivated')." user {$lockedTarget->email}",
                auditable: $lockedTarget,
                targetUser: $lockedTarget,
                beforeState: $before,
                afterState: $after,
            );

            return $lockedTarget;
        });
    }

    /**
     * @param  list<string>  $roleNames
     * @param  list<string>  $directPermissions
     *
     * @throws AuthorizationException
     */
    public function syncAccess(User $actor, User $target, array $roleNames, array $directPermissions = []): User
    {
        if (! $actor->can('roles.assign')) {
            throw new AuthorizationException('You do not have permission to assign roles or permissions.');
        }

        return DB::transaction(function () use ($actor, $target, $roleNames, $directPermissions): User {
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->id);
            $before = $this->snapshotUser($lockedTarget);

            $this->guard->assertCanAssignRoles($actor, $lockedTarget, $roleNames);
            $this->guard->assertCanAssignDirectPermissions($actor, $lockedTarget, $directPermissions);

            $lockedTarget->syncRoles($roleNames);
            $lockedTarget->syncPermissions($directPermissions);

            $lockedTarget->refresh()->load(['roles', 'permissions']);
            $after = $this->snapshotUser($lockedTarget);

            $this->audit->record(
                actor: $actor,
                event: 'user.permissions_updated',
                summary: "Updated roles/permissions for {$lockedTarget->email}",
                auditable: $lockedTarget,
                targetUser: $lockedTarget,
                beforeState: $before,
                afterState: $after,
            );

            return $lockedTarget;
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function deleteUser(User $actor, User $target): void
    {
        if (! $actor->can('users.delete')) {
            throw new AuthorizationException('You do not have permission to delete users.');
        }

        if ($actor->is($target)) {
            throw new InvalidArgumentException('You cannot delete your own account.');
        }

        $this->guard->assertCanManageTargetUser($actor, $target);

        DB::transaction(function () use ($actor, $target): void {
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($target->id);
            $this->guard->assertNotLastActiveSuperAdmin($lockedTarget);

            $before = $this->snapshotUser($lockedTarget);

            $this->audit->record(
                actor: $actor,
                event: 'user.deleted',
                summary: "Deleted user {$lockedTarget->email}",
                auditable: $lockedTarget,
                targetUser: null,
                beforeState: $before,
                afterState: null,
            );

            $lockedTarget->delete();
        });
    }

    /**
     * Structured breakdown for User Permissions Preview (role-derived vs direct vs effective).
     *
     * @return array{roles: list<string>, role_derived: list<string>, direct: list<string>, effective: list<string>}
     */
    public function permissionBreakdown(User $user): array
    {
        $roles = $user->getRoleNames()->sort()->values()->all();
        $roleDerived = $user->getPermissionsViaRoles()->pluck('name')->unique()->sort()->values()->all();
        $direct = $user->getDirectPermissions()->pluck('name')->unique()->sort()->values()->all();
        $effective = $user->getAllPermissions()->pluck('name')->unique()->sort()->values()->all();

        return [
            'roles' => $roles,
            'role_derived' => $roleDerived,
            'direct' => $direct,
            'effective' => $effective,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotUser(User $user): array
    {
        $breakdown = $this->permissionBreakdown($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'account_type' => $user->account_type?->value,
            'is_active' => (bool) $user->is_active,
            'roles' => $breakdown['roles'],
            'direct_permissions' => $breakdown['direct'],
            'effective_permissions' => $breakdown['effective'],
        ];
    }
}
