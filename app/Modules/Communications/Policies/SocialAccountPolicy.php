<?php

namespace App\Modules\Communications\Policies;

use App\Models\User;
use App\Modules\Communications\Models\SocialAccount;

final class SocialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.manage') || $user->can('cms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('social.manage');
    }

    public function update(User $user, SocialAccount $account): bool
    {
        return $user->can('social.manage');
    }

    public function delete(User $user, SocialAccount $account): bool
    {
        return $user->can('social.manage');
    }
}
