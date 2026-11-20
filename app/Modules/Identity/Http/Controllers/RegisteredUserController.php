<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Identity\Http\Requests\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'account_type' => AccountType::Customer,
            'is_active' => true,
        ]);

        $customerRole = Role::findOrCreate('Customer', 'web');
        $user->assignRole($customerRole);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('status', 'Account created. Please verify your email address to access your travel dashboard.');
    }
}
