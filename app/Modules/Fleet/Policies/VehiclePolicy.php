<?php

namespace App\Modules\Fleet\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class VehiclePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('vehicles.view');
    }

    public function view(User $actor, Model $record): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('vehicles.manage');
    }

    public function update(User $actor, Model $record): bool
    {
        return $actor->isActiveStaff() && ($actor->can('vehicles.manage') || $actor->can('vehicles.rates.manage'));
    }

    public function delete(User $actor, Model $record): bool
    {
        return $actor->isActiveStaff() && $actor->can('vehicles.manage');
    }
}
