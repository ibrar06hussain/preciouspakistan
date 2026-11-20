@extends('layouts.public')

@section('title', 'Forgot Password — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-md px-4 py-12 sm:py-16">
        <x-ui.card class="space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Reset your password</h1>
                <p class="text-sm text-slate-600">Enter your email address and we will send a secure password reset link.</p>
            </div>

            @if (session('status'))
                <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

                <x-ui.input
                    label="Email Address"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                />

                <x-ui.button type="submit" variant="primary" class="w-full">
                    Send Reset Link
                </x-ui.button>
            </form>

            <p class="text-center text-sm text-slate-600">
                <a href="{{ route('login') }}" class="font-semibold text-emerald-800 hover:text-emerald-900">Back to Sign In</a>
            </p>
        </x-ui.card>
    </div>
@endsection
