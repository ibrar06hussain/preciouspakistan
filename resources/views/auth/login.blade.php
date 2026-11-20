@extends('layouts.public')

@section('title', 'Sign In — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-md px-4 py-12 sm:py-16">
        <x-ui.card class="space-y-6">
            <div class="space-y-2 text-center">
                <x-ui.badge tone="emerald">Account Access</x-ui.badge>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sign in to Precious Pakistan</h1>
                <p class="text-sm text-slate-600">Access your traveler dashboard, quotes, and itineraries.</p>
            </div>

            @if (session('status'))
                <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf

                <x-ui.input
                    label="Email Address"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="traveler@example.com"
                />

                <x-ui.input
                    label="Password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                />

                <div class="flex items-center justify-between text-sm">
                    <label class="inline-flex items-center gap-2 text-slate-700 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-emerald-800 focus:ring-emerald-700">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="font-semibold text-emerald-800 hover:text-emerald-900">
                        Forgot password?
                    </a>
                </div>

                <x-ui.button type="submit" variant="primary" class="w-full">
                    Sign In
                </x-ui.button>
            </form>

            <p class="text-center text-sm text-slate-600">
                New to Precious Pakistan?
                <a href="{{ route('register') }}" class="font-semibold text-emerald-800 hover:text-emerald-900">Create a traveler account</a>
            </p>
        </x-ui.card>
    </div>
@endsection
