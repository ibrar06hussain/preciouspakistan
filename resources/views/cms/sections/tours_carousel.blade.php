<section id="tours-showcase" class="{{ $styleClass }}">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="$config['eyebrow'] ?? 'Curated Journeys'"
            :title="$config['heading'] ?? 'Featured Pakistan Tours'"
            :subtitle="$config['subtitle'] ?? null"
        />

        <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach ($config['items'] ?? [] as $tour)
                <x-ui.card class="flex flex-col justify-between bg-slate-50/70">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            @if ($tour['is_sample'] ?? true)
                                <x-ui.badge tone="demo">Sample Preview</x-ui.badge>
                            @else
                                <x-ui.badge tone="emerald">Tour Package</x-ui.badge>
                            @endif
                            <span class="text-xs font-semibold text-slate-600">{{ $tour['duration'] ?? '' }}</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900">{{ $tour['title'] ?? '' }}</h3>
                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">{{ $tour['region'] ?? '' }}</p>
                        <p class="text-sm leading-relaxed text-slate-600">{{ $tour['summary'] ?? '' }}</p>
                    </div>
                    <div class="mt-6 flex items-center justify-between border-t border-slate-200 pt-4">
                        <div>
                            <span class="block text-[11px] font-medium uppercase tracking-wider text-slate-500">Pricing</span>
                            <span class="text-sm font-bold text-slate-900">{{ $tour['price_display'] ?? 'Request Quote' }}</span>
                        </div>
                        <x-ui.button href="{{ $tour['cta_href'] ?? '/contact' }}" variant="outline" size="sm">Request Quote</x-ui.button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </div>
</section>
