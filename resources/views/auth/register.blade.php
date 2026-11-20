@extends('layouts.public')

@section('title', 'Create Traveler Account — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-md px-4 py-12 sm:py-16">
        <x-ui.card class="space-y-6">
            <div class="space-y-2 text-center">
                <x-ui.badge tone="emerald">Traveler Registration</x-ui.badge>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create your account</h1>
                <p class="text-sm text-slate-600">Save inquiries, review PKR/USD quotations, and track your confirmed Pakistan journeys.</p>
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                @csrf

                <x-ui.input
                    label="Full Name"
                    name="name"
                    type="text"
                    required
                    autocomplete="name"
                    placeholder="Ayesha Khan"
                />

                <x-ui.input
                    label="Email Address"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="ayesha@example.com"
                />

                <x-ui.input
                    label="Phone / WhatsApp (Optional)"
                    name="phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="+92 300 0000000"
                />

                <x-ui.input
                    label="Password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    hint="At least 8 characters including letters and numbers."
                />

                <x-ui.input
                    label="Confirm Password"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                />

                <x-ui.button type="submit" variant="primary" class="w-full">
                    Create Traveler Account
                </x-ui.button>
            </form>

            <p class="text-center text-sm text-slate-600">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-emerald-800 hover:text-emerald-900">Sign in</a>
            </p>
        </x-ui.card>
    </div>
@endsection
