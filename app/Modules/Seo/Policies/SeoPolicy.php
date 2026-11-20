<?php

namespace App\Modules\Seo\Policies;

use App\Models\User;
use App\Modules\Destinations\Models\UrlRedirect;

final class SeoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('seo.view');
    }

    public function create(User $user): bool
    {
        return $user->can('seo.update');
    }

    public function update(User $user, UrlRedirect $redirect): bool
    {
        return $user->can('seo.update');
    }

    public function delete(User $user, UrlRedirect $redirect): bool
    {
        return $user->can('seo.update');
    }
}
