<?php

namespace App\Modules\Communications\Policies;

use App\Models\User;
use App\Modules\Communications\Models\Inquiry;

final class InquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inquiries.view');
    }

    public function view(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.view') || ($inquiry->user_id !== null && $inquiry->user_id === $user->id);
    }

    public function update(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.respond');
    }
}
