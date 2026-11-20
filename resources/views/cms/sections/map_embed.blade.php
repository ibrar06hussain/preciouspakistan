<section class="{{ $styleClass }}">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 space-y-6">
        <x-ui.section-heading
            :eyebrow="$config['eyebrow'] ?? 'Geographic Coverage'"
            :title="$config['heading'] ?? 'Destinations & Operations Map'"
            :subtitle="$config['subtitle'] ?? null"
        />

        <div
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4"
            data-map-center-lat="{{ $config['center_lat'] ?? 33.6844 }}"
            data-map-center-lng="{{ $config['center_lng'] ?? 73.0479 }}"
            data-map-zoom="{{ $config['zoom'] ?? 6 }}"
        >
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                <span class="font-semibold text-slate-900">
                    Center Coordinates: {{ number_format((float) ($config['center_lat'] ?? 33.6844), 4) }}° N, {{ number_format((float) ($config['center_lng'] ?? 73.0479), 4) }}° E
                </span>
                <x-ui.badge tone="emerald">Leaflet GeoJSON Ready (Phase 4)</x-ui.badge>
            </div>

            @if (! empty($config['markers']))
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($config['markers'] as $marker)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                            <p class="text-sm font-bold text-slate-900">{{ $marker['title'] ?? '' }}</p>
                            @if (filled($marker['region'] ?? null))
                                <p class="text-xs font-semibold text-emerald-800">{{ $marker['region'] }}</p>
                            @endif
                            <p class="text-xs text-slate-600">{{ $marker['description'] ?? '' }}</p>
                            <p class="text-[11px] font-mono text-slate-500">
                                {{ number_format((float) ($marker['lat'] ?? 0), 4) }}, {{ number_format((float) ($marker['lng'] ?? 0), 4) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
