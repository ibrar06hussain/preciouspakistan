<?php

namespace App\Modules\Booking\Policies;

use App\Models\User;
use App\Modules\Booking\Models\Booking;

final class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bookings.view');
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->can('bookings.view') || ($booking->user_id !== null && $booking->user_id === $user->id);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->can('bookings.update');
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return $user->can('bookings.confirm');
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->can('bookings.cancel');
    }
}
