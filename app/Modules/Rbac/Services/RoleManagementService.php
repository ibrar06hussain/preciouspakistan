<?php

namespace App\Modules\Rbac\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleManagementService
{
    public function __construct(
        private readonly PrivilegeGuard $guard,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<string>  $permissionNames
     *
     * @throws AuthorizationException
     */
    public function createRole(User $actor, string $name, array $permissionNames = []): Role
    {
        if (! $actor->can('roles.create')) {
            throw new AuthorizationException('You do not have permission to create roles.');
        }

        $name = trim($name);
        $permissionNames = array_values(array_unique(array_filter(array_map('trim', $permissionNames))));

        $this->guard->assertCanSaveRole($actor, $name, $permissionNames, null);

        $role = DB::transaction(function () use ($actor, $name, $permissionNames): Role {
            $role = Role::query()->create([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissionNames);

            $after = $this->snapshotRole($role->fresh('permissions'));

            $this->audit->record(
                actor: $actor,
                event: 'role.created',
                summary: "Created role {$role->name}",
                auditable: $role,
                targetUser: null,
                beforeState: null,
                afterState: $after,
            );

            return $role;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }

    /**
     * @param  list<string>  $permissionNames
     *
     * @throws AuthorizationException
     */
    public function updateRole(User $actor, Role $role, string $name, array $permissionNames = []): Role
    {
        if (! $actor->can('roles.update')) {
            throw new AuthorizationException('You do not have permission to update roles.');
        }

        $name = trim($name);
        $permissionNames = array_values(array_unique(array_filter(array_map('trim', $permissionNames))));

        $this->guard->assertCanSaveRole($actor, $name, $permissionNames, $role);

        $updated = DB::transaction(function () use ($actor, $role, $name, $permissionNames): Role {
            $lockedRole = Role::query()->lockForUpdate()->findOrFail($role->id);
            $before = $this->snapshotRole($lockedRole->load('permissions'));

            $lockedRole->forceFill(['name' => $name])->save();
            $lockedRole->syncPermissions($permissionNames);

            $after = $this->snapshotRole($lockedRole->fresh('permissions'));

            if ($before !== $after) {
                $this->audit->record(
                    actor: $actor,
                    event: 'role.updated',
                    summary: "Updated role {$lockedRole->name}",
                    auditable: $lockedRole,
                    targetUser: null,
                    beforeState: $before,
                    afterState: $after,
                );
            }

            return $lockedRole;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $updated;
    }

    /**
     * @throws AuthorizationException
     */
    public function deleteRole(User $actor, Role $role): void
    {
        if (! $actor->can('roles.delete')) {
            throw new AuthorizationException('You do not have permission to delete roles.');
        }

        $this->guard->assertCanDeleteRole($actor, $role);

        DB::transaction(function () use ($actor, $role): void {
            $lockedRole = Role::query()->lockForUpdate()->findOrFail($role->id);
            $before = $this->snapshotRole($lockedRole->load('permissions'));

            $this->audit->record(
                actor: $actor,
                event: 'role.deleted',
                summary: "Deleted role {$lockedRole->name}",
                auditable: $lockedRole,
                targetUser: null,
                beforeState: $before,
                afterState: null,
            );

            $lockedRole->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
        ];
    }
}
