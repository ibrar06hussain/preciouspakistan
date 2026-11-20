@extends('layouts.public')

@section('title', 'Offline Itinerary — '.$booking->booking_reference)

@section('content')
<section class="py-10 bg-white">
    <div class="max-w-3xl mx-auto px-4 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Offline-Ready Itinerary Pack</span>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $booking->tour?->title }}</h1>
                <p class="text-sm text-slate-600">Booking {{ $booking->booking_reference }} · {{ $booking->start_date->format('d M Y') }} – {{ $booking->end_date->format('d M Y') }}</p>
            </div>
            <button type="button" onclick="if('caches' in window){caches.open('pp-offline-itineraries-v1').then(c => c.add(window.location.href)).then(() => alert('Itinerary saved for offline reading on this device!'));}" class="px-4 py-2 rounded-lg bg-emerald-700 text-white text-xs font-semibold hover:bg-emerald-800">
                Download for Offline Reading
            </button>
        </div>

        <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900">
            Offline mode supports reading downloaded itineraries only. Booking changes, reservations, and payments require an active internet connection.
        </div>

        <div class="space-y-4">
            <h2 class="text-lg font-bold text-slate-900">Day-by-Day Itinerary</h2>
            @foreach($booking->tour?->itineraryDays ?? [] as $day)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="font-semibold text-slate-900">Day {{ $day->day_number }}: {{ $day->title }}</div>
                    <div class="mt-1 text-sm text-slate-700">{!! $day->description_html !!}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
