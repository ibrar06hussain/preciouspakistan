@extends('layouts.public')

@section('title', 'Company Fleet & Driver-Included Vehicles — Precious Pakistan')
@section('meta_description', 'Browse Precious Pakistan company-managed 4x4 SUVs, vans, sedans, and coasters with drivers always included and transparent seasonal with-fuel or without-fuel rates.')

@push('head_meta')
    <link rel="canonical" href="{{ route('fleet.index') }}">
@endpush

@section('content')
    <section class="pp-hero-pattern text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 space-y-4">
            <x-ui.badge tone="dark">Driver Always Included — Zero Extra Driver Fee</x-ui.badge>
            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                Company-Managed Pakistan Tour Fleet
            </h1>
            <p class="max-w-3xl text-sm leading-relaxed text-emerald-50/90 sm:text-base">
                Select a company vehicle as part of your Pakistan tour. Every rate includes a professional driver; choose transparently between <strong>with-fuel</strong> and <strong>without-fuel</strong> seasonal daily pricing.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 space-y-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @foreach ($vehicles as $vehicle)
                @php
                    $firstRate = $vehicle->seasonalRates->first();
                @endphp
                <x-ui.card class="flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <x-ui.badge tone="emerald">{{ \App\Modules\Fleet\Models\Vehicle::TYPES[$vehicle->vehicle_type] ?? ucfirst($vehicle->vehicle_type) }}</x-ui.badge>
                            <div class="flex gap-1.5">
                                <x-ui.badge tone="amber">Driver: INCLUDED</x-ui.badge>
                                @if ($vehicle->is_sample_content)
                                    <x-ui.badge tone="demo">Sample Fleet</x-ui.badge>
                                @endif
                            </div>
                        </div>

                        <h2 class="text-xl font-bold text-slate-900">{{ $vehicle->name }}</h2>

                        <p class="text-xs font-semibold text-slate-600">
                            Up to {{ $vehicle->passenger_capacity }} Passengers
                            &bull; {{ $vehicle->luggage_capacity }} Bags
                            &bull; {{ ucfirst($vehicle->transmission) }}
                            &bull; {{ $vehicle->has_ac ? 'A/C Equipped' : 'Standard' }}
                        </p>

                        @if ($vehicle->summary)
                            <p class="text-sm leading-relaxed text-slate-600">{{ $vehicle->summary }}</p>
                        @endif

                        @if ($firstRate)
                            <div class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs">
                                <div>
                                    <span class="block uppercase tracking-wider text-slate-500">With Fuel / Day</span>
                                    <span class="mt-0.5 block text-sm font-bold text-slate-900">
                                        {{ $firstRate->with_fuel_rate_pkr ? 'PKR '.number_format((float) $firstRate->with_fuel_rate_pkr, 0) : 'Request Quote' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block uppercase tracking-wider text-slate-500">Without Fuel / Day</span>
                                    <span class="mt-0.5 block text-sm font-bold text-slate-900">
                                        {{ $firstRate->without_fuel_rate_pkr ? 'PKR '.number_format((float) $firstRate->without_fuel_rate_pkr, 0) : 'Request Quote' }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 pt-4 text-xs">
                        <span class="text-slate-600">
                            Routes: {{ collect($vehicle->routing_suitability ?? [])->map(fn ($r) => \App\Modules\Fleet\Models\Vehicle::ROUTE_TAGS[$r] ?? $r)->take(2)->join(', ') }}
                        </span>
                        <x-ui.button href="{{ route('tours.index') }}" variant="outline" size="sm">Pair With a Tour</x-ui.button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </section>
@endsection
