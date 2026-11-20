<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Finance\Models\Invoice;

final class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view') || ($invoice->user_id !== null && $invoice->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.create');
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.issue');
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.void');
    }
}
