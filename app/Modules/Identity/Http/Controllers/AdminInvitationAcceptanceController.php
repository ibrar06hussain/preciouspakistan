<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Http\Requests\AcceptAdminInvitationRequest;
use App\Modules\Identity\Services\AdminInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class AdminInvitationAcceptanceController extends Controller
{
    public function show(string $token, AdminInvitationService $service): View
    {
        $invitation = $service->findUsableByPlainToken($token);

        if ($invitation === null) {
            abort(404, 'This staff invitation is invalid, expired, or has already been accepted.');
        }

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function store(string $token, AcceptAdminInvitationRequest $request, AdminInvitationService $service): RedirectResponse
    {
        try {
            $user = $service->acceptInvitation(
                $token,
                $request->string('password')->toString(),
                $request->input('phone')
            );
        } catch (Throwable $exception) {
            return back()->withErrors(['token' => $exception->getMessage()]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/admin')->with('status', 'Staff account activated. Welcome to Precious Pakistan Admin.');
    }
}
