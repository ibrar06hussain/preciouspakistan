@extends('layouts.public')

@section('title', $hotel->seo_title ?: $hotel->name.' — Tour Accommodation Info — Precious Pakistan')
@section('meta_description', $hotel->seo_description ?: 'Tour-only accommodation option in '.$hotel->destination?->name.' offered exclusively within Precious Pakistan tours.')

@push('head_meta')
    <link rel="canonical" href="{{ $hotel->canonical_url ?: $hotel->publicInfoUrl() }}">
    @if ($hotel->noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
@endpush

@section('content')
    <section class="pp-hero-pattern text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge tone="amber">Offered Only Within Precious Pakistan Tours</x-ui.badge>
                @if ($hotel->is_sample_content)
                    <x-ui.badge tone="demo">Sample Listing</x-ui.badge>
                @endif
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ $hotel->name }}</h1>
            <p class="text-sm text-emerald-100">
                {{ $hotel->destination?->name }}@if ($hotel->region), {{ $hotel->region->name }}@endif
                @if ($hotel->address) &bull; {{ $hotel->address }} @endif
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 grid grid-cols-1 gap-8 lg:grid-cols-12">
        <div class="lg:col-span-8 space-y-6">
            <x-ui.alert type="info">
                <strong>Tour-Only Accommodation Notice:</strong> Standalone hotel bookings are not available. Select this property inside an eligible Precious Pakistan tour package; a company booking officer externally verifies availability before final confirmation.
            </x-ui.alert>

            @if (filled($hotel->short_description_html))
                <x-ui.card class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">Property Overview</h2>
                    <div class="text-sm leading-relaxed text-slate-700 space-y-2">
                        {!! $hotel->short_description_html !!}
                    </div>
                </x-ui.card>
            @endif

            <div class="space-y-4">
                <h2 class="text-xl font-bold text-slate-900">Room Types &amp; Seasonal Nightly Guide Rates</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($hotel->roomTypes as $room)
                        <x-ui.card class="space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-base font-bold text-slate-900">{{ $room->name }}</h3>
                                <x-ui.badge tone="slate">Max {{ $room->max_occupancy }} guests</x-ui.badge>
                            </div>
                            @if ($room->bed_configuration)
                                <p class="text-xs text-slate-600">{{ $room->bed_configuration }}</p>
                            @endif
                            @if ($room->rates->isNotEmpty())
                                <ul class="space-y-1 border-t border-slate-100 pt-2 text-xs text-slate-700">
                                    @foreach ($room->rates->take(3) as $rate)
                                        <li>
                                            <strong>{{ $rate->rate_name }}</strong> ({{ $rate->valid_from->format('M Y') }}–{{ $rate->valid_to->format('M Y') }}):
                                            @if ($rate->nightly_rate_pkr) PKR {{ number_format((float) $rate->nightly_rate_pkr, 0) }}/night @endif
                                            @if ($rate->nightly_rate_usd) &bull; USD {{ number_format((float) $rate->nightly_rate_usd, 2) }}/night @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
        </div>

        <aside class="lg:col-span-4 space-y-6">
            <x-ui.card class="space-y-4">
                <x-ui.badge tone="emerald">Eligible Tours</x-ui.badge>
                <h2 class="text-lg font-bold text-slate-900">Bookable Within These Tours</h2>
                @if ($eligibleTours->isEmpty())
                    <p class="text-sm text-slate-600">Contact our team to include this stay in a custom tour package.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($eligibleTours as $tour)
                            <a href="{{ $tour->publicUrl() }}" class="block rounded-xl border border-slate-200 bg-slate-50 p-3.5 hover:border-emerald-400 transition">
                                <p class="text-sm font-bold text-slate-900">{{ $tour->title }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $tour->duration_days }}D/{{ $tour->duration_nights }}N &bull; {{ $tour->displayPrice('PKR') }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
                <x-ui.button href="{{ route('tours.index') }}" variant="primary" class="w-full">Browse All Tours</x-ui.button>
            </x-ui.card>
        </aside>
    </section>
@endsection
