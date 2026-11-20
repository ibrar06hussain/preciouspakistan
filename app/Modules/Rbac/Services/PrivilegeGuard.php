<?php

namespace App\Modules\Rbac\Services;

use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Rbac\Support\RbacCatalog;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

final class PrivilegeGuard
{
    public function isSuperAdmin(User $user): bool
    {
        return $user->isActiveStaff()
            && $user->hasVerifiedEmail()
            && $user->hasRole(RbacCatalog::ROLE_SUPER_ADMIN);
    }

    public function assertValidRoleName(string $name): void
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Role name cannot be empty.');
        }

        if (RbacCatalog::isForbiddenRoleName($trimmed)) {
            throw new InvalidArgumentException('Hotel partner and open-vendor roles are not permitted on this company-operated platform.');
        }
    }

    /**
     * Ensure actor cannot mutate a Super Admin unless actor is also an active Super Admin.
     *
     * @throws AuthorizationException
     */
    public function assertCanManageTargetUser(User $actor, User $target): void
    {
        if ($target->hasRole(RbacCatalog::ROLE_SUPER_ADMIN) && ! $this->isSuperAdmin($actor)) {
            throw new AuthorizationException('Only an active Super Admin may modify, deactivate, or delete a Super Admin account.');
        }
    }

    /**
     * Prevent privilege escalation when assigning roles to a user.
     *
     * @param  list<string>  $roleNames
     *
     * @throws AuthorizationException
     */
    public function assertCanAssignRoles(User $actor, User $target, array $roleNames): void
    {
        $this->assertCanManageTargetUser($actor, $target);

        $roleNames = array_values(array_unique(array_filter(array_map('trim', $roleNames))));

        foreach ($roleNames as $roleName) {
            $this->assertValidRoleName($roleName);
        }

        if ($target->account_type === AccountType::Customer) {
            $nonCustomerRoles = array_diff($roleNames, [RbacCatalog::ROLE_CUSTOMER]);
            if ($nonCustomerRoles !== []) {
                throw new InvalidArgumentException('Customer accounts cannot be assigned staff roles.');
            }
        }

        if ($target->account_type === AccountType::Staff && in_array(RbacCatalog::ROLE_CUSTOMER, $roleNames, true)) {
            throw new InvalidArgumentException('Staff accounts cannot be assigned the Customer role.');
        }

        $actorIsSuperAdmin = $this->isSuperAdmin($actor);

        if ($actor->is($target) && ! $actorIsSuperAdmin) {
            throw new AuthorizationException('Non-Super-Admin users cannot modify their own assigned roles.');
        }

        $currentlySuperAdmin = $target->hasRole(RbacCatalog::ROLE_SUPER_ADMIN);
        $willBeSuperAdmin = in_array(RbacCatalog::ROLE_SUPER_ADMIN, $roleNames, true);

        if (($willBeSuperAdmin || $currentlySuperAdmin) && ! $actorIsSuperAdmin) {
            throw new AuthorizationException('Only an active Super Admin may assign or revoke the Super Admin role.');
        }

        if ($currentlySuperAdmin && ! $willBeSuperAdmin) {
            $this->assertNotLastActiveSuperAdmin($target);
        }

        if (! $actorIsSuperAdmin) {
            $actorPermissions = $actor->getAllPermissions()->pluck('name')->all();
            $requestedRoles = Role::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $roleNames)
                ->with('permissions')
                ->get();

            foreach ($requestedRoles as $role) {
                $rolePermissions = $role->permissions->pluck('name')->all();
                $exceeding = array_diff($rolePermissions, $actorPermissions);

                if ($exceeding !== []) {
                    throw new AuthorizationException(
                        sprintf('Privilege escalation denied: role [%s] grants permissions you do not hold.', $role->name)
                    );
                }
            }
        }
    }

    /**
     * Prevent privilege escalation when assigning direct permissions to a user.
     *
     * @param  list<string>  $permissionNames
     *
     * @throws AuthorizationException
     */
    public function assertCanAssignDirectPermissions(User $actor, User $target, array $permissionNames): void
    {
        $this->assertCanManageTargetUser($actor, $target);

        $permissionNames = array_values(array_unique(array_filter(array_map('trim', $permissionNames))));

        if ($target->account_type === AccountType::Customer && $permissionNames !== []) {
            throw new InvalidArgumentException('Customer accounts cannot be granted staff direct permissions.');
        }

        if ($this->isSuperAdmin($actor)) {
            return;
        }

        if ($actor->is($target)) {
            throw new AuthorizationException('Non-Super-Admin users cannot modify their own direct permissions.');
        }

        $actorPermissions = $actor->getAllPermissions()->pluck('name')->all();
        $exceeding = array_diff($permissionNames, $actorPermissions);

        if ($exceeding !== []) {
            throw new AuthorizationException('Privilege escalation denied: you cannot grant permissions you do not possess.');
        }
    }

    /**
     * Guard creating or editing a role and its attached permissions.
     *
     * @param  list<string>  $permissionNames
     *
     * @throws AuthorizationException
     */
    public function assertCanSaveRole(User $actor, string $roleName, array $permissionNames, ?Role $existingRole = null): void
    {
        $this->assertValidRoleName($roleName);

        if ($existingRole !== null && RbacCatalog::isProtectedSystemRole($existingRole->name) && $existingRole->name !== $roleName) {
            throw new InvalidArgumentException("System role [{$existingRole->name}] cannot be renamed.");
        }

        if ($existingRole !== null && $existingRole->name === RbacCatalog::ROLE_CUSTOMER && $permissionNames !== []) {
            throw new InvalidArgumentException('The Customer role cannot be granted staff permissions.');
        }

        $actorIsSuperAdmin = $this->isSuperAdmin($actor);

        if ($existingRole !== null && $existingRole->name === RbacCatalog::ROLE_SUPER_ADMIN && ! $actorIsSuperAdmin) {
            throw new AuthorizationException('Only an active Super Admin may modify the Super Admin role.');
        }

        if ($roleName === RbacCatalog::ROLE_SUPER_ADMIN && ! $actorIsSuperAdmin) {
            throw new AuthorizationException('Only an active Super Admin may manage the Super Admin role.');
        }

        if (! $actorIsSuperAdmin) {
            $actorPermissions = $actor->getAllPermissions()->pluck('name')->all();
            $exceeding = array_diff($permissionNames, $actorPermissions);

            if ($exceeding !== []) {
                throw new AuthorizationException('Privilege escalation denied: you cannot assign permissions you do not hold to a role.');
            }
        }
    }

    /**
     * Guard deleting a role.
     *
     * @throws AuthorizationException
     */
    public function assertCanDeleteRole(User $actor, Role $role): void
    {
        if (RbacCatalog::isProtectedSystemRole($role->name)) {
            throw new InvalidArgumentException("System role [{$role->name}] cannot be deleted.");
        }

        if (! $this->isSuperAdmin($actor)) {
            $actorPermissions = $actor->getAllPermissions()->pluck('name')->all();
            $rolePermissions = $role->permissions()->pluck('name')->all();

            if (array_diff($rolePermissions, $actorPermissions) !== []) {
                throw new AuthorizationException('You cannot delete a role that holds permissions above your own.');
            }
        }
    }

    /**
     * Guard staff invitation role against privilege escalation and prohibited roles.
     *
     * @throws AuthorizationException
     */
    public function assertCanInviteRole(User $actor, string $roleName): void
    {
        $this->assertValidRoleName($roleName);

        if (strcasecmp($roleName, RbacCatalog::ROLE_CUSTOMER) === 0) {
            throw new InvalidArgumentException('Staff invitations cannot assign the Customer role.');
        }

        if ($this->isSuperAdmin($actor)) {
            return;
        }

        if ($roleName === RbacCatalog::ROLE_SUPER_ADMIN) {
            throw new AuthorizationException('Only an active Super Admin may invite another Super Admin.');
        }

        $role = Role::query()->where('guard_name', 'web')->where('name', $roleName)->with('permissions')->first();

        if ($role === null) {
            throw new InvalidArgumentException("Role [{$roleName}] does not exist.");
        }

        $actorPermissions = $actor->getAllPermissions()->pluck('name')->all();
        $rolePermissions = $role->permissions->pluck('name')->all();

        if (array_diff($rolePermissions, $actorPermissions) !== []) {
            throw new AuthorizationException("Privilege escalation denied: you cannot invite a user into role [{$roleName}].");
        }
    }

    /**
     * Lock and guarantee at least one OTHER active verified Super Admin exists.
     */
    public function assertNotLastActiveSuperAdmin(User $target): void
    {
        if (! $target->hasRole(RbacCatalog::ROLE_SUPER_ADMIN)) {
            return;
        }

        $otherActiveSuperAdmins = User::query()
            ->role(RbacCatalog::ROLE_SUPER_ADMIN)
            ->where('users.id', '!=', $target->id)
            ->where('account_type', AccountType::Staff->value)
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->lockForUpdate()
            ->count();

        if ($otherActiveSuperAdmins < 1) {
            throw new InvalidArgumentException('Cannot deactivate, delete, or demote the last active Super Admin.');
        }
    }
}
