<section id="digital-nomads" class="bg-emerald-950 text-white">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-7 space-y-4">
                @if (filled($config['badge'] ?? null))
                    <x-ui.badge tone="dark">{{ $config['badge'] }}</x-ui.badge>
                @endif
                <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ $config['heading'] ?? 'Digital Nomad Tour Add-Ons' }}</h2>
                <p class="text-sm leading-relaxed text-emerald-100/90 sm:text-base">{{ $config['body'] ?? '' }}</p>

                @if (filled($config['connectivity_disclaimer'] ?? null))
                    <p class="rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-xs text-amber-100">
                        <strong>Transparency Notice:</strong> {{ $config['connectivity_disclaimer'] }}
                    </p>
                @endif

                @if (filled($config['cta_label'] ?? null) && filled($config['cta_href'] ?? null))
                    <div class="pt-2">
                        <x-ui.button href="{{ $config['cta_href'] }}" variant="accent">{{ $config['cta_label'] }}</x-ui.button>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-5">
                @if (! empty($config['highlights']))
                    <ul class="space-y-3 rounded-2xl border border-white/15 bg-white/10 p-6 text-sm text-emerald-50">
                        @foreach ($config['highlights'] as $item)
                            <li class="flex items-start gap-2.5">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-amber-400" aria-hidden="true"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</section>
