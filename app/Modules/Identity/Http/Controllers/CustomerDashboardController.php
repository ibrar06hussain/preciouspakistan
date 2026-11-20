<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Models\Booking;
use App\Modules\Communications\Models\Inquiry;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Identity\Contracts\MfaProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request, MfaProvider $mfaProvider): View
    {
        Gate::authorize('accessCustomerDashboard');

        $user = $request->user();

        $bookings = Booking::query()
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere('customer_email', $user->email);
            })
            ->with(['tour'])
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $invoices = Invoice::query()
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere('customer_email', $user->email);
            })
            ->whereNotNull('issued_at')
            ->orderByDesc('issued_at')
            ->limit(25)
            ->get();

        $inquiries = Inquiry::query()
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('customer.dashboard', [
            'user' => $user,
            'mfaConfigured' => $mfaProvider->isConfigured(),
            'bookings' => $bookings,
            'invoices' => $invoices,
            'inquiries' => $inquiries,
        ]);
    }
}
