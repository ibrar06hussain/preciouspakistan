<section class="bg-emerald-950 text-white">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl space-y-3">
                @if (filled($config['badge'] ?? null))
                    <x-ui.badge tone="dark">{{ $config['badge'] }}</x-ui.badge>
                @endif
                <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ $config['heading'] ?? '' }}</h2>
                @if (filled($config['body'] ?? null))
                    <p class="text-sm leading-relaxed text-emerald-100/90 sm:text-base">{{ $config['body'] }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-3">
                @if (filled($config['primary_cta_label'] ?? null) && filled($config['primary_cta_href'] ?? null))
                    <x-ui.button href="{{ $config['primary_cta_href'] }}" variant="accent" size="lg">{{ $config['primary_cta_label'] }}</x-ui.button>
                @endif
                @if (filled($config['secondary_cta_label'] ?? null) && filled($config['secondary_cta_href'] ?? null))
                    <x-ui.button href="{{ $config['secondary_cta_href'] }}" variant="outline-light" size="lg">{{ $config['secondary_cta_label'] }}</x-ui.button>
                @endif
            </div>
        </div>
    </div>
</section>
