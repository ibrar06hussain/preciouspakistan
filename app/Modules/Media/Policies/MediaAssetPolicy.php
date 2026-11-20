<?php

namespace App\Modules\Media\Policies;

use App\Models\User;
use App\Modules\Media\Models\MediaAsset;

final class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cms.view') || $user->can('tours.view') || $user->can('destinations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cms.update') || $user->can('tours.update') || $user->can('destinations.manage');
    }

    public function update(User $user, MediaAsset $asset): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, MediaAsset $asset): bool
    {
        return $user->can('cms.publish') || $user->can('tours.delete');
    }
}
