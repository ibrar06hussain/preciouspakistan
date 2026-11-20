<?php

namespace App\Modules\Rbac\Policies;

use App\Models\User;
use App\Modules\Rbac\Support\RbacCatalog;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('roles.view');
    }

    public function view(User $actor, Role $role): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('roles.create');
    }

    public function update(User $actor, Role $role): bool
    {
        if (! ($actor->isActiveStaff() && $actor->can('roles.update'))) {
            return false;
        }

        if ($role->name === RbacCatalog::ROLE_SUPER_ADMIN && ! $actor->hasRole(RbacCatalog::ROLE_SUPER_ADMIN)) {
            return false;
        }

        return true;
    }

    public function delete(User $actor, Role $role): bool
    {
        if (! ($actor->isActiveStaff() && $actor->can('roles.delete'))) {
            return false;
        }

        if (RbacCatalog::isProtectedSystemRole($role->name)) {
            return false;
        }

        return true;
    }
}
