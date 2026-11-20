@extends('layouts.public')

@section('title', $destination->seo_title ?: $destination->name.' — '.$destination->region?->name.' — Precious Pakistan')
@section('meta_description', $destination->seo_description ?: $destination->summary)

@push('head_meta')
    <link rel="canonical" href="{{ $destination->canonical_url ?: $destination->publicUrl() }}">
    @if ($destination->noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
@endpush

@section('content')
    <section class="pp-hero-pattern text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 space-y-4">
            <nav aria-label="Breadcrumb" class="text-xs font-medium text-emerald-200 flex flex-wrap items-center gap-2">
                <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('destinations.index') }}" class="hover:text-white">Destinations</a>
                @if ($destination->region)
                    <span aria-hidden="true">/</span>
                    <a href="{{ $destination->region->publicUrl() }}" class="hover:text-white">{{ $destination->region->name }}</a>
                @endif
                <span aria-hidden="true">/</span>
                <span class="text-white">{{ $destination->name }}</span>
            </nav>

            <div class="flex flex-wrap items-center gap-2">
                @if ($destination->region)
                    <x-ui.badge tone="dark">{{ $destination->region->name }}</x-ui.badge>
                @endif
                @if ($destination->district)
                    <x-ui.badge tone="dark">District: {{ $destination->district->name }}</x-ui.badge>
                @endif
                @if ($destination->is_sample_content)
                    <x-ui.badge tone="demo">Curated Sample Profile</x-ui.badge>
                @endif
            </div>

            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $destination->name }}</h1>
            <p class="max-w-3xl text-sm leading-relaxed text-emerald-50/90 sm:text-base">{{ $destination->summary }}</p>

            <dl class="grid grid-cols-2 gap-4 pt-4 border-t border-white/15 sm:grid-cols-4 max-w-2xl">
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-emerald-200">Best Season</dt>
                    <dd class="mt-0.5 text-sm font-bold text-white">{{ $destination->best_season ?: 'Year-round' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-emerald-200">Elevation</dt>
                    <dd class="mt-0.5 text-sm font-bold text-white">{{ $destination->elevation_meters ? number_format($destination->elevation_meters).' m' : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-emerald-200">Coordinates</dt>
                    <dd class="mt-0.5 text-xs font-mono text-white">
                        {{ $destination->latitude !== null ? number_format($destination->latitude, 4).'°, '.number_format($destination->longitude, 4).'°' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-emerald-200">Nearby Sights</dt>
                    <dd class="mt-0.5 text-sm font-bold text-white">{{ $nearbyAttractions->count() }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 grid grid-cols-1 gap-8 lg:grid-cols-12">
        <div class="lg:col-span-8 space-y-8">
            @if (filled($destination->description_html))
                <x-ui.card class="space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">About {{ $destination->name }}</h2>
                    <div class="space-y-3 text-sm leading-relaxed text-slate-700">
                        {!! $destination->description_html !!}
                    </div>
                </x-ui.card>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-3 text-sm font-bold text-slate-900">
                    Location &amp; Nearby Attractions Map
                </div>
                <div
                    data-leaflet-map
                    data-center-lat="{{ $destination->latitude ?? 33.6844 }}"
                    data-center-lng="{{ $destination->longitude ?? 73.0479 }}"
                    data-zoom="9"
                    data-geojson-id="destination-detail-geojson"
                    class="h-80 w-full z-0"
                    role="region"
                    aria-label="Map of {{ $destination->name }} and nearby attractions"
                ></div>
                <script id="destination-detail-geojson" type="application/json">{!! json_encode($geoJson, JSON_UNESCAPED_SLASHES) !!}</script>
            </div>

            <div class="space-y-4">
                <h2 class="text-xl font-bold text-slate-900">Attractions &amp; Nearby Highlights</h2>

                @if ($nearbyAttractions->isEmpty())
                    <x-ui.card>
                        <p class="text-sm text-slate-600">No attractions have been cataloged near {{ $destination->name }} yet.</p>
                    </x-ui.card>
                @else
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($nearbyAttractions as $entry)
                            @php
                                $attr = $entry['attraction'];
                            @endphp
                            <x-ui.card class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <x-ui.badge tone="amber">{{ \App\Modules\Destinations\Models\Attraction::CATEGORIES[$attr->category] ?? ucfirst($attr->category) }}</x-ui.badge>
                                    <span class="text-xs font-semibold text-slate-600">
                                        @if ($entry['is_local'])
                                            In {{ $destination->name }}
                                        @elseif ($entry['distance_km'] !== null)
                                            ~{{ $entry['distance_km'] }} km away
                                        @endif
                                    </span>
                                </div>
                                <h3 class="text-base font-bold text-slate-900">{{ $attr->name }}</h3>
                                <p class="text-xs leading-relaxed text-slate-600">{{ $attr->summary }}</p>
                            </x-ui.card>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <aside class="lg:col-span-4 space-y-6">
            <x-ui.card class="space-y-4">
                <x-ui.badge tone="emerald">Related Tours</x-ui.badge>
                <h2 class="text-lg font-bold text-slate-900">Tours Visiting {{ $destination->name }}</h2>
                @if ($relatedTours->isEmpty())
                    <p class="text-sm text-slate-600">
                        Published tour packages linked to {{ $destination->name }} will appear here automatically once the Tour Catalog goes live in Phase 5.
                    </p>
                @endif
                <x-ui.button href="/contact" variant="primary" class="w-full">
                    Request Custom {{ $destination->name }} Quote
                </x-ui.button>
            </x-ui.card>
        </aside>
    </section>
@endsection
