<?php

namespace App\Modules\Rbac\Policies;

use App\Models\User;
use App\Modules\Identity\Models\AdminInvitation;

class AdminInvitationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && ($actor->can('users.invite') || $actor->can('users.view'));
    }

    public function view(User $actor, AdminInvitation $invitation): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('users.invite');
    }

    public function revoke(User $actor, AdminInvitation $invitation): bool
    {
        return $actor->isActiveStaff() && $actor->can('users.invite') && $invitation->isUsable();
    }

    public function update(User $actor, AdminInvitation $invitation): bool
    {
        return false;
    }

    public function delete(User $actor, AdminInvitation $invitation): bool
    {
        return false;
    }
}
