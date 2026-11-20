@extends('layouts.public')

@section('title', 'Traveler Dashboard — Precious Pakistan')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <x-ui.badge tone="emerald">Verified Traveler</x-ui.badge>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Welcome back, {{ $user->name }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Local display time: @pktDate(now())
                </p>
            </div>

            <x-ui.button href="{{ route('home') }}" variant="outline" size="sm">
                Back to Explore
            </x-ui.button>
        </div>

        @if (session('status'))
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <x-ui.card class="space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Booking Requests</p>
                <p class="text-2xl font-bold text-slate-900">0</p>
                <p class="text-xs text-slate-600">Tour booking and quote tracking will appear here once Phase 5 &amp; Phase 9 go live.</p>
            </x-ui.card>

            <x-ui.card class="space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">PKR / USD Invoices</p>
                <p class="text-2xl font-bold text-slate-900">0</p>
                <p class="text-xs text-slate-600">Immutable quotations, receipts, and downloadable vouchers will appear here.</p>
            </x-ui.card>

            <x-ui.card class="space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Account Security</p>
                <p class="text-sm font-semibold text-emerald-800">Email Verified</p>
                <p class="text-xs text-slate-600">
                    Optional MFA: {{ $mfaConfigured ? 'Available' : 'Not enabled on this environment yet' }}
                </p>
            </x-ui.card>
        </div>
    </div>
@endsection
