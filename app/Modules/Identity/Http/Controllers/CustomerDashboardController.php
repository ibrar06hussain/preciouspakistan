<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Contracts\MfaProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request, MfaProvider $mfaProvider): View
    {
        Gate::authorize('accessCustomerDashboard');

        return view('customer.dashboard', [
            'user' => $request->user(),
            'mfaConfigured' => $mfaProvider->isConfigured(),
        ]);
    }
}
