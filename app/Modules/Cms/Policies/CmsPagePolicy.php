<?php

namespace App\Modules\Cms\Policies;

use App\Models\User;
use App\Modules\Cms\Models\CmsPage;

class CmsPagePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('cms.view');
    }

    public function view(User $actor, CmsPage $page): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->isActiveStaff() && $actor->can('cms.update');
    }

    public function update(User $actor, CmsPage $page): bool
    {
        return $actor->isActiveStaff() && $actor->can('cms.update');
    }

    public function publish(User $actor, CmsPage $page): bool
    {
        return $actor->isActiveStaff() && $actor->can('cms.publish');
    }

    public function delete(User $actor, CmsPage $page): bool
    {
        return $actor->isActiveStaff() && $actor->can('cms.publish') && ! $page->is_homepage;
    }
}
