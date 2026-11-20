<?php

namespace App\Modules\Identity\Policies;

use App\Models\User;

class AdminAccessPolicy
{
    public function accessPanel(User $user): bool
    {
        return $user->isActiveStaff()
            && $user->hasVerifiedEmail()
            && $user->hasStaffRole();
    }

    public function inviteStaff(User $user): bool
    {
        return $this->accessPanel($user)
            && $user->hasAnyRole(['Super Admin', 'Administrator']);
    }
}
