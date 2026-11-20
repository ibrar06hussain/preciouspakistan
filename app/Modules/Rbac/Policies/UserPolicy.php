<?php

namespace App\Modules\Rbac\Policies;

use App\Models\User;
use App\Modules\Rbac\Support\RbacCatalog;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        if (! ($actor->isActiveStaff() && $actor->can('users.update'))) {
            return false;
        }

        if ($target->hasRole(RbacCatalog::ROLE_SUPER_ADMIN) && ! $actor->hasRole(RbacCatalog::ROLE_SUPER_ADMIN)) {
            return false;
        }

        return true;
    }

    public function delete(User $actor, User $target): bool
    {
        if (! ($actor->isActiveStaff() && $actor->can('users.delete'))) {
            return false;
        }

        if ($actor->is($target)) {
            return false;
        }

        if ($target->hasRole(RbacCatalog::ROLE_SUPER_ADMIN) && ! $actor->hasRole(RbacCatalog::ROLE_SUPER_ADMIN)) {
            return false;
        }

        return true;
    }

    public function deactivate(User $actor, User $target): bool
    {
        if (! ($actor->isActiveStaff() && $actor->can('users.deactivate'))) {
            return false;
        }

        if ($target->hasRole(RbacCatalog::ROLE_SUPER_ADMIN) && ! $actor->hasRole(RbacCatalog::ROLE_SUPER_ADMIN)) {
            return false;
        }

        return true;
    }

    public function assignRoles(User $actor, User $target): bool
    {
        if (! ($actor->isActiveStaff() && $actor->can('roles.assign'))) {
            return false;
        }

        if ($target->hasRole(RbacCatalog::ROLE_SUPER_ADMIN) && ! $actor->hasRole(RbacCatalog::ROLE_SUPER_ADMIN)) {
            return false;
        }

        return true;
    }
}
