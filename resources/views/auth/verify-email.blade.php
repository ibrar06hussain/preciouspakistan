@extends('layouts.public')

@section('title', 'Verify Email — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-12 sm:py-16">
        <x-ui.card class="space-y-5">
            <x-ui.badge tone="amber">Email Verification Required</x-ui.badge>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Verify your email address</h1>
            <p class="text-sm leading-relaxed text-slate-600">
                Before accessing your traveler dashboard, please click the verification link we emailed to
                <strong class="text-slate-900">{{ auth()->user()?->email }}</strong>.
            </p>

            @if (session('status'))
                <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
            @endif

            <div class="flex flex-wrap items-center gap-3 pt-2">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-ui.button type="submit" variant="primary">Resend Verification Email</x-ui.button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui.button type="submit" variant="outline">Sign Out</x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </div>
@endsection
