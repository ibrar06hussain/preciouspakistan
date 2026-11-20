<section id="regions" class="{{ $styleClass }}">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="$config['eyebrow'] ?? 'All-Pakistan Destinations'"
            :title="$config['heading'] ?? 'Explore Every Region of Pakistan'"
            :subtitle="$config['subtitle'] ?? null"
        />

        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($config['regions'] ?? [] as $region)
                <x-ui.card class="flex flex-col justify-between hover:border-emerald-300 hover:shadow-md transition">
                    <div class="space-y-2.5">
                        <x-ui.badge tone="emerald">{{ $region['tag'] ?? 'Pakistan' }}</x-ui.badge>
                        <h3 class="text-lg font-bold text-slate-900">{{ $region['name'] ?? '' }}</h3>
                        <p class="text-sm leading-relaxed text-slate-600">{{ $region['highlights'] ?? '' }}</p>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </div>
</section>
