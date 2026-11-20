<?php

namespace App\Modules\Tours\Policies;

use App\Models\User;
use App\Modules\Tours\Models\Tour;

class TourPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('tours.view');
    }

    public function view(User $actor, Tour $tour): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('tours.create');
    }

    public function update(User $actor, Tour $tour): bool
    {
        return $actor->isActiveStaff() && $actor->can('tours.update');
    }

    public function publish(User $actor, Tour $tour): bool
    {
        return $actor->isActiveStaff() && $actor->can('tours.publish');
    }

    public function delete(User $actor, Tour $tour): bool
    {
        return $actor->isActiveStaff() && $actor->can('tours.delete');
    }
}
