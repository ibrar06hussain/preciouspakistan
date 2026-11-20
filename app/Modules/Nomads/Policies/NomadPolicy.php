<?php

namespace App\Modules\Nomads\Policies;

use App\Models\User;
use App\Modules\Nomads\Models\NomadLocation;

class NomadPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('nomads.view');
    }

    public function view(User $actor, NomadLocation $location): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('nomads.manage');
    }

    public function update(User $actor, NomadLocation $location): bool
    {
        return $actor->isActiveStaff() && $actor->can('nomads.manage');
    }

    public function delete(User $actor, NomadLocation $location): bool
    {
        return $actor->isActiveStaff() && $actor->can('nomads.manage');
    }
}
