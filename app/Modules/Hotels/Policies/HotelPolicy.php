<?php

namespace App\Modules\Hotels\Policies;

use App\Models\User;
use App\Modules\Hotels\Models\Hotel;

class HotelPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('hotels.view');
    }

    public function view(User $actor, Hotel $hotel): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('hotels.manage');
    }

    public function update(User $actor, Hotel $hotel): bool
    {
        return $actor->isActiveStaff() && $actor->can('hotels.manage');
    }

    public function delete(User $actor, Hotel $hotel): bool
    {
        return $actor->isActiveStaff() && $actor->can('hotels.manage');
    }
}
