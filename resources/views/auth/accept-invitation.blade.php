@extends('layouts.public')

@section('title', 'Accept Staff Invitation — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-md px-4 py-12 sm:py-16">
        <x-ui.card class="space-y-6">
            <div class="space-y-2 text-center">
                <x-ui.badge tone="emerald">Company Staff Onboarding</x-ui.badge>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Activate your staff account</h1>
                <p class="text-sm text-slate-600">
                    Invited as <strong class="text-slate-900">{{ $invitation->role_name }}</strong>
                    for <strong class="text-slate-900">{{ $invitation->email }}</strong>.
                </p>
            </div>

            @error('token')
                <x-ui.alert type="error">{{ $message }}</x-ui.alert>
            @enderror

            <form method="POST" action="{{ route('admin.invitations.accept', ['token' => $token]) }}" class="space-y-4">
                @csrf

                <x-ui.input
                    label="Phone Number (Optional)"
                    name="phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="+92 300 0000000"
                />

                <x-ui.input
                    label="Create Strong Password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    hint="Minimum 12 characters with uppercase, lowercase, number, and symbol."
                />

                <x-ui.input
                    label="Confirm Password"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                />

                <x-ui.button type="submit" variant="primary" class="w-full">
                    Activate Staff Account
                </x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
