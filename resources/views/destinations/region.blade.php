@extends('layouts.public')

@section('title', $region->seo_title ?: $region->name.' — Precious Pakistan')
@section('meta_description', $region->seo_description ?: $region->summary)

@push('head_meta')
    <link rel="canonical" href="{{ $region->canonical_url ?: $region->publicUrl() }}">
    @if ($region->noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
@endpush

@section('content')
    <section class="pp-hero-pattern text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 space-y-4">
            <nav aria-label="Breadcrumb" class="text-xs font-medium text-emerald-200 flex items-center gap-2">
                <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('destinations.index') }}" class="hover:text-white">Destinations</a>
                <span aria-hidden="true">/</span>
                <span class="text-white">{{ $region->name }}</span>
            </nav>

            <x-ui.badge tone="dark">{{ str_replace('_', ' ', ucfirst($region->region_type)) }}</x-ui.badge>
            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $region->name }}</h1>
            @if ($region->tagline)
                <p class="text-base font-medium text-amber-300">{{ $region->tagline }}</p>
            @endif
            @if ($region->summary)
                <p class="max-w-3xl text-sm leading-relaxed text-emerald-50/90 sm:text-base">{{ $region->summary }}</p>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 space-y-10">
        @if ($region->districts->isNotEmpty())
            <div class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">Curated Districts in {{ $region->name }}</h2>
                    <span class="text-xs text-slate-500">Seeded incrementally for tourism coverage</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($region->districts as $district)
                        <a
                            href="{{ route('destinations.index', ['region' => $region->slug, 'district' => $district->slug]) }}"
                            class="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-800 hover:border-emerald-700 hover:text-emerald-900"
                        >
                            {{ $district->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-3 text-sm font-bold text-slate-900">
                {{ $region->name }} Destinations Map
            </div>
            <div
                data-leaflet-map
                data-center-lat="{{ $region->latitude ?? 30.3753 }}"
                data-center-lng="{{ $region->longitude ?? 69.3451 }}"
                data-zoom="{{ $region->default_zoom ?? 7 }}"
                data-geojson-id="region-geojson"
                class="h-80 w-full z-0"
                role="region"
                aria-label="Interactive map of {{ $region->name }}"
            ></div>
            <script id="region-geojson" type="application/json">{!! json_encode($geoJson, JSON_UNESCAPED_SLASHES) !!}</script>
        </div>

        <div class="space-y-5">
            <h2 class="text-2xl font-bold text-slate-900">Destinations in {{ $region->name }}</h2>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($region->destinations as $destination)
                    <x-ui.card class="flex flex-col justify-between hover:border-emerald-300 hover:shadow-md transition">
                        <div class="space-y-2.5">
                            @if ($destination->district)
                                <x-ui.badge tone="slate">{{ $destination->district->name }}</x-ui.badge>
                            @endif
                            <h3 class="text-xl font-bold text-slate-900">
                                <a href="{{ $destination->publicUrl() }}" class="hover:text-emerald-800">{{ $destination->name }}</a>
                            </h3>
                            <p class="text-sm text-slate-600">{{ $destination->summary }}</p>
                        </div>
                        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
                            <span class="text-slate-600">{{ $destination->best_season ?: 'Seasonal' }}</span>
                            <a href="{{ $destination->publicUrl() }}" class="font-bold text-emerald-800 hover:text-emerald-950">View Guide &rarr;</a>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    </section>
@endsection
