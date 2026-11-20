@extends('layouts.public')

@section('title', 'Explore Pakistan Destinations — Precious Pakistan')
@section('meta_description', 'Browse destinations across Gilgit-Baltistan, Azad Jammu & Kashmir, Khyber Pakhtunkhwa, Punjab, Sindh, Balochistan, and Islamabad Capital Territory.')

@push('head_meta')
    <link rel="canonical" href="{{ route('destinations.index') }}">
@endpush

@section('content')
    <section class="pp-hero-pattern text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 space-y-5">
            <x-ui.badge tone="dark">All-Pakistan Geographic Explorer</x-ui.badge>
            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                Destinations Across Every Region of Pakistan
            </h1>
            <p class="max-w-3xl text-sm leading-relaxed text-emerald-50/90 sm:text-base">
                Filter curated destinations across Punjab, Sindh, Khyber Pakhtunkhwa, Balochistan, Gilgit-Baltistan, Azad Jammu &amp; Kashmir, and Islamabad Capital Territory.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 space-y-8">
        {{-- Region Quick Links --}}
        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('destinations.index') }}"
                class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition {{ $selectedRegionSlug === null ? 'border-emerald-800 bg-emerald-800 text-white' : 'border-slate-300 bg-white text-slate-800 hover:border-emerald-700' }}"
            >
                All Regions
            </a>
            @foreach ($regions as $region)
                <a
                    href="{{ route('regions.show', ['slug' => $region->slug]) }}"
                    class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition {{ $selectedRegionSlug === $region->slug ? 'border-emerald-800 bg-emerald-800 text-white' : 'border-slate-300 bg-white text-slate-800 hover:border-emerald-700' }}"
                >
                    {{ $region->name }} ({{ $region->destinations_count }})
                </a>
            @endforeach
        </div>

        {{-- Search & Filters --}}
        <x-ui.card>
            <form method="GET" action="{{ route('destinations.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12 items-end">
                <div class="lg:col-span-4 space-y-1">
                    <label for="geo-q" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Keyword</label>
                    <input
                        id="geo-q"
                        type="search"
                        name="q"
                        value="{{ $searchQuery }}"
                        placeholder="Hunza, Skardu, Swat, Mohenjo-daro..."
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900"
                    >
                </div>

                <div class="lg:col-span-3 space-y-1">
                    <label for="geo-region" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Region / Province</label>
                    <select id="geo-region" name="region" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                        <option value="">All 7 Regions</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->slug }}" @selected($selectedRegionSlug === $region->slug)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-3 space-y-1">
                    <label for="geo-district" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Curated District</label>
                    <select id="geo-district" name="district" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                        <option value="">Any District</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->slug }}" @selected($selectedDistrictSlug === $district->slug)>{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2 flex gap-2">
                    <x-ui.button type="submit" variant="primary" class="w-full">Filter</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        {{-- Interactive Leaflet Map --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-3">
                <span class="text-sm font-bold text-slate-900">Interactive Pakistan Destination Map ({{ $destinations->count() }} shown)</span>
                <span class="text-xs text-slate-600">Curated destination examples &mdash; not an exhaustive administrative census</span>
            </div>
            <div
                data-leaflet-map
                data-center-lat="30.3753"
                data-center-lng="69.3451"
                data-zoom="5"
                data-geojson-id="destinations-index-geojson"
                class="h-80 sm:h-96 w-full z-0"
                role="region"
                aria-label="Interactive map of Pakistan tourism destinations"
            ></div>
            <script id="destinations-index-geojson" type="application/json">{!! json_encode($geoJson, JSON_UNESCAPED_SLASHES) !!}</script>
        </div>

        {{-- Destinations Grid --}}
        @if ($destinations->isEmpty())
            <x-ui.alert type="info">
                No destinations matched your current filters. Try clearing the search keyword or selecting another region.
            </x-ui.alert>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($destinations as $destination)
                    <x-ui.card class="flex flex-col justify-between hover:border-emerald-300 hover:shadow-md transition">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <x-ui.badge tone="emerald">{{ $destination->region?->name }}</x-ui.badge>
                                @if ($destination->best_season)
                                    <span class="text-xs font-semibold text-slate-600">{{ $destination->best_season }}</span>
                                @endif
                            </div>

                            <h2 class="text-xl font-bold text-slate-900">
                                <a href="{{ $destination->publicUrl() }}" class="hover:text-emerald-800">{{ $destination->name }}</a>
                            </h2>

                            @if ($destination->district)
                                <p class="text-xs font-medium text-slate-500">District: {{ $destination->district->name }}</p>
                            @endif

                            <p class="text-sm leading-relaxed text-slate-600">{{ $destination->summary }}</p>
                        </div>

                        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-xs">
                            <span class="font-medium text-slate-600">
                                {{ $destination->attractions_count }} {{ \Illuminate\Support\Str::plural('attraction', $destination->attractions_count) }}
                                @if ($destination->elevation_meters)
                                    &bull; {{ number_format($destination->elevation_meters) }} m
                                @endif
                            </span>
                            <a href="{{ $destination->publicUrl() }}" class="font-bold text-emerald-800 hover:text-emerald-950">
                                Explore &rarr;
                            </a>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </section>
@endsection
