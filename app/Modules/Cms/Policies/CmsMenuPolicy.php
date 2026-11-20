<?php

namespace App\Modules\Cms\Policies;

use App\Models\User;
use App\Modules\Cms\Models\CmsMenu;

class CmsMenuPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('cms.view');
    }

    public function view(User $actor, CmsMenu $menu): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('cms.update');
    }

    public function update(User $actor, CmsMenu $menu): bool
    {
        return $actor->isActiveStaff() && $actor->can('cms.update');
    }

    public function delete(User $actor, CmsMenu $menu): bool
    {
        return $actor->isActiveStaff() && $actor->can('cms.publish');
    }
}
