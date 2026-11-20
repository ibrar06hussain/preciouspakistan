<?php

namespace App\Modules\Hotels\Policies;

use App\Models\User;
use App\Modules\Hotels\Models\HotelAvailabilityVerification;

class HotelVerificationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && ($actor->can('hotels.view') || $actor->can('bookings.confirm'));
    }

    public function view(User $actor, HotelAvailabilityVerification $verification): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, HotelAvailabilityVerification $verification): bool
    {
        return $actor->isActiveStaff() && ($actor->can('bookings.confirm') || $actor->can('hotels.manage'));
    }

    public function delete(User $actor, HotelAvailabilityVerification $verification): bool
    {
        return false;
    }
}
