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

            <div class="flex gap-2">
                <x-ui.button href="{{ route('tours.index') }}" variant="primary" size="sm">
                    Browse Tours
                </x-ui.button>
                <x-ui.button href="{{ route('home') }}" variant="outline" size="sm">
                    Back to Explore
                </x-ui.button>
            </div>
        </div>

        @if (session('status'))
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <x-ui.card class="space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Booking Requests</p>
                <p class="text-2xl font-bold text-slate-900">{{ $bookings->count() }}</p>
                <p class="text-xs text-slate-600">Tour booking status and payment status are tracked transparently.</p>
            </x-ui.card>

            <x-ui.card class="space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Issued PKR / USD Invoices</p>
                <p class="text-2xl font-bold text-slate-900">{{ $invoices->count() }}</p>
                <p class="text-xs text-slate-600">Immutable PDFs with QR authenticity verification.</p>
            </x-ui.card>

            <x-ui.card class="space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Account &amp; Inquiries</p>
                <p class="text-sm font-semibold text-emerald-800">Email Verified · {{ $inquiries->count() }} Inquiry(ies)</p>
                <p class="text-xs text-slate-600">
                    Optional MFA: {{ $mfaConfigured ? 'Available' : 'Not enabled on this environment yet' }}
                </p>
            </x-ui.card>
        </div>

        <x-ui.card class="space-y-4">
            <h2 class="text-lg font-bold text-slate-900">Your Tour Bookings &amp; Offline Itineraries</h2>
            @forelse($bookings as $booking)
                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4 text-sm">
                    <div>
                        <div class="font-semibold text-slate-900">{{ $booking->booking_reference }} — {{ $booking->tour?->title }}</div>
                        <div class="text-xs text-slate-500">{{ $booking->start_date->format('d M Y') }} to {{ $booking->end_date->format('d M Y') }} · {{ $booking->currency }} {{ number_format((float) $booking->total_amount, 2) }}</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800">{{ $booking->booking_status }}</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">{{ $booking->payment_status }}</span>
                        <a href="{{ route('bookings.itinerary-offline', ['reference' => $booking->booking_reference]) }}" class="px-3 py-1.5 rounded-lg border border-emerald-600 text-emerald-700 text-xs font-semibold hover:bg-emerald-50">
                            Save Offline Itinerary
                        </a>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">You have not submitted any tour booking requests yet.</p>
            @endforelse
        </x-ui.card>

        <x-ui.card class="space-y-4">
            <h2 class="text-lg font-bold text-slate-900">Your Issued Invoices</h2>
            @forelse($invoices as $invoice)
                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4 text-sm">
                    <div>
                        <div class="font-semibold text-slate-900">{{ $invoice->invoice_number }} ({{ strtoupper($invoice->invoice_type) }})</div>
                        <div class="text-xs text-slate-500">Total: {{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }} · Balance: {{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}</div>
                    </div>
                    <a href="{{ route('invoices.pdf', ['invoice' => $invoice->id]) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-emerald-700 text-white text-xs font-semibold hover:bg-emerald-800">
                        Download PDF
                    </a>
                </div>
            @empty
                <p class="text-sm text-slate-500">No issued invoices yet.</p>
            @endforelse
        </x-ui.card>
    </div>
@endsection
