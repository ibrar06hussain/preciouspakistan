<?php

namespace App\Modules\Identity\Policies;

use App\Models\User;

class CustomerAccessPolicy
{
    public function accessDashboard(User $user): bool
    {
        return $user->is_active
            && $user->isCustomer()
            && $user->hasVerifiedEmail();
    }
}
