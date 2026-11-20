@extends('layouts.public')

@section('title', 'Pakistan Tour Packages — Precious Pakistan')
@section('meta_description', 'Browse company-operated sightseeing, trekking, cultural, honeymoon, group, and custom private tours across Pakistan.')

@push('head_meta')
    <link rel="canonical" href="{{ route('tours.index') }}">
@endpush

@section('content')
    <section class="pp-hero-pattern text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 space-y-4">
            <x-ui.badge tone="dark">Company-Operated Tour Catalog</x-ui.badge>
            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                Curated Pakistan Tour Packages
            </h1>
            <p class="max-w-3xl text-sm leading-relaxed text-emerald-50/90 sm:text-base">
                Private, group, trekking, cultural, and Digital Nomad-ready itineraries operated directly by Precious Pakistan with included-driver company fleet and verified tour stays.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 space-y-8">
        {{-- Category Pills --}}
        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('tours.index') }}"
                class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition {{ empty($filters['category']) ? 'border-emerald-800 bg-emerald-800 text-white' : 'border-slate-300 bg-white text-slate-800 hover:border-emerald-700' }}"
            >
                All Categories
            </a>
            @foreach (\App\Modules\Tours\Models\Tour::CATEGORIES as $catKey => $catLabel)
                <a
                    href="{{ route('tours.index', array_filter(['category' => $catKey, 'region' => $filters['region'] ?? null])) }}"
                    class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition {{ ($filters['category'] ?? null) === $catKey ? 'border-emerald-800 bg-emerald-800 text-white' : 'border-slate-300 bg-white text-slate-800 hover:border-emerald-700' }}"
                >
                    {{ $catLabel }}
                </a>
            @endforeach
        </div>

        {{-- Multi-facet Filter Form --}}
        <x-ui.card>
            <form method="GET" action="{{ route('tours.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12 items-end">
                <div class="lg:col-span-3 space-y-1">
                    <label for="tour-q" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Search</label>
                    <input id="tour-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Hunza, Skardu, Trek..." class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900">
                </div>

                <div class="lg:col-span-2 space-y-1">
                    <label for="tour-region" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Region</label>
                    <select id="tour-region" name="region" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                        <option value="">All Regions</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->slug }}" @selected(($filters['region'] ?? null) === $region->slug)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2 space-y-1">
                    <label for="tour-category" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Category</label>
                    <select id="tour-category" name="category" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                        <option value="">Any Category</option>
                        @foreach (\App\Modules\Tours\Models\Tour::CATEGORIES as $k => $v)
                            <option value="{{ $k }}" @selected(($filters['category'] ?? null) === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-1 space-y-1">
                    <label for="tour-guests" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Guests</label>
                    <input id="tour-guests" type="number" name="guests" min="1" max="50" value="{{ $filters['guests'] ?? '' }}" placeholder="2" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                </div>

                <div class="lg:col-span-2 space-y-1">
                    <label for="tour-sort" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Sort</label>
                    <select id="tour-sort" name="sort" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                        <option value="featured" @selected(($filters['sort'] ?? 'featured') === 'featured')>Featured</option>
                        <option value="duration_asc" @selected(($filters['sort'] ?? '') === 'duration_asc')>Shortest First</option>
                        <option value="duration_desc" @selected(($filters['sort'] ?? '') === 'duration_desc')>Longest First</option>
                        <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Lowest Base Price</option>
                    </select>
                </div>

                <div class="lg:col-span-2 space-y-2">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="nomad" value="1" @checked(! empty($filters['nomad'])) class="rounded border-slate-300 text-emerald-800">
                        <span>Digital Nomad Ready</span>
                    </label>
                    <x-ui.button type="submit" variant="primary" class="w-full">Apply Filters</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($tours->isEmpty())
            <x-ui.alert type="info">No published tours matched your selected filters.</x-ui.alert>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($tours as $tour)
                    <x-ui.card class="flex flex-col justify-between hover:border-emerald-300 hover:shadow-md transition">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <x-ui.badge tone="emerald">{{ \App\Modules\Tours\Models\Tour::CATEGORIES[$tour->category] ?? ucfirst($tour->category) }}</x-ui.badge>
                                @if ($tour->is_sample_content)
                                    <x-ui.badge tone="demo">Sample Tour</x-ui.badge>
                                @endif
                            </div>

                            <h2 class="text-xl font-bold text-slate-900">
                                <a href="{{ $tour->publicUrl() }}" class="hover:text-emerald-800">{{ $tour->title }}</a>
                            </h2>

                            <p class="text-xs font-semibold text-slate-600">
                                {{ $tour->duration_days }} Days / {{ $tour->duration_nights }} Nights
                                &bull; {{ $tour->min_guests }}–{{ $tour->max_guests }} Guests
                                &bull; {{ \App\Modules\Tours\Models\Tour::DIFFICULTIES[$tour->difficulty] ?? ucfirst($tour->difficulty) }}
                            </p>

                            @if ($tour->destinations->isNotEmpty())
                                <p class="text-xs font-medium text-emerald-800">
                                    Stops: {{ $tour->destinations->pluck('name')->join(', ') }}
                                </p>
                            @endif

                            <p class="text-sm leading-relaxed text-slate-600">{{ $tour->summary }}</p>

                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @if ($tour->requires_company_vehicle)
                                    <x-ui.badge tone="slate">Driver-Included Fleet</x-ui.badge>
                                @endif
                                @if ($tour->supports_nomad_addon)
                                    <x-ui.badge tone="amber">Nomad Add-On</x-ui.badge>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                            <div>
                                <span class="block text-[11px] uppercase tracking-wider text-slate-500">Guide Rate</span>
                                <span class="text-sm font-bold text-slate-900">{{ $tour->displayPrice('PKR') }}</span>
                            </div>
                            <x-ui.button href="{{ $tour->publicUrl() }}" variant="outline" size="sm">View Itinerary</x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </section>
@endsection
