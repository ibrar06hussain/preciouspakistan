<section id="fleet" class="{{ $styleClass }}">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <x-ui.section-heading
                :eyebrow="$config['eyebrow'] ?? 'Company-Managed Fleet'"
                :title="$config['heading'] ?? 'Tour Vehicles with Included Drivers'"
                :subtitle="$config['subtitle'] ?? null"
            />
            <x-ui.badge tone="emerald">Driver Always Included</x-ui.badge>
        </div>

        @if (filled($config['driver_note'] ?? null))
            <p class="mt-4 text-sm font-medium text-emerald-900 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                {{ $config['driver_note'] }}
            </p>
        @endif

        <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach ($config['items'] ?? [] as $vehicle)
                <x-ui.card class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <x-ui.badge tone="{{ ($vehicle['is_sample'] ?? true) ? 'demo' : 'emerald' }}">
                            {{ ($vehicle['is_sample'] ?? true) ? 'Sample Fleet' : ($vehicle['category'] ?? 'Fleet') }}
                        </x-ui.badge>
                        <span class="text-xs font-semibold text-slate-600">{{ $vehicle['seats'] ?? '' }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">{{ $vehicle['name'] ?? '' }}</h3>
                    <p class="text-xs font-semibold text-emerald-800">Driver: INCLUDED &bull; {{ $vehicle['fuel_note'] ?? '' }}</p>
                    <p class="text-sm text-slate-600">{{ $vehicle['summary'] ?? '' }}</p>
                </x-ui.card>
            @endforeach
        </div>
    </div>
</section>
