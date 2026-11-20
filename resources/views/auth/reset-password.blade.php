@extends('layouts.public')

@section('title', 'Choose New Password — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-md px-4 py-12 sm:py-16">
        <x-ui.card class="space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Set a new password</h1>
                <p class="text-sm text-slate-600">Choose a strong new password for your Precious Pakistan account.</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <x-ui.input
                    label="Email Address"
                    name="email"
                    type="email"
                    :value="$email"
                    required
                    autocomplete="email"
                />

                <x-ui.input
                    label="New Password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                />

                <x-ui.input
                    label="Confirm New Password"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                />

                <x-ui.button type="submit" variant="primary" class="w-full">
                    Update Password
                </x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
