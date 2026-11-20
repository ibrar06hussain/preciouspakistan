@extends('layouts.public')

@section('title', 'Booking Request '.$booking->booking_reference)

@section('content')
<section class="py-12 bg-slate-50 min-h-[70vh]">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                        {{ \App\Modules\Booking\Models\Booking::BOOKING_STATUSES[$booking->booking_status] ?? $booking->booking_status }}
                    </span>
                    <h1 class="mt-2 text-2xl font-bold text-slate-900">Booking {{ $booking->booking_reference }}</h1>
                    <p class="text-sm text-slate-600">{{ $booking->tour?->title }} · {{ $booking->start_date->format('d M Y') }} to {{ $booking->end_date->format('d M Y') }} · {{ $booking->guests_count }} guest(s)</p>
                </div>
                <div class="text-right">
                    <div class="text-xs uppercase tracking-wider text-slate-500">Payment Status</div>
                    <div class="font-semibold text-slate-800">{{ \App\Modules\Booking\Models\Booking::PAYMENT_STATUSES[$booking->payment_status] ?? $booking->payment_status }}</div>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-3">Authoritative Price Snapshot ({{ $booking->currency }})</h2>
                <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl">
                    @foreach($booking->items as $item)
                        <div class="flex items-center justify-between px-4 py-3 text-sm">
                            <div>
                                <div class="font-medium text-slate-900">{{ $item->label }}</div>
                                <div class="text-xs text-slate-500">{{ $item->quantity }} {{ $item->charge_unit }}</div>
                            </div>
                            <div class="font-semibold text-slate-900">
                                @if($item->is_included)
                                    <span class="text-emerald-700">INCLUDED</span>
                                @else
                                    {{ $booking->currency }} {{ number_format((float) $item->line_total, 2) }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex justify-end text-right space-y-1">
                    <div>
                        <div class="text-sm text-slate-600">Subtotal: {{ $booking->currency }} {{ number_format((float) $booking->subtotal, 2) }}</div>
                        @if((float) $booking->discount_amount > 0)
                            <div class="text-sm text-emerald-700">Discount: -{{ $booking->currency }} {{ number_format((float) $booking->discount_amount, 2) }}</div>
                        @endif
                        @if((float) $booking->tax_amount > 0)
                            <div class="text-sm text-slate-600">Tax ({{ $booking->tax_rate_percent }}%): {{ $booking->currency }} {{ number_format((float) $booking->tax_amount, 2) }}</div>
                        @endif
                        <div class="text-lg font-bold text-slate-900 mt-1">Total: {{ $booking->currency }} {{ number_format((float) $booking->total_amount, 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-xs text-slate-700">
                Booking status and payment status are tracked separately. If your booking includes tour-only hotels, our Booking Officer externally verifies room availability before confirming your itinerary and issuing a final invoice.
            </div>
        </div>
    </div>
</section>
@endsection
