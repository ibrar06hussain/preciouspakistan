<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\FxRateSnapshot;

final class FxRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('fx.manage') || $user->can('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('fx.manage');
    }

    public function update(User $user, FxRateSnapshot $snapshot): bool
    {
        return false; // FX snapshots are append-only historical records.
    }

    public function delete(User $user, FxRateSnapshot $snapshot): bool
    {
        return false;
    }
}
