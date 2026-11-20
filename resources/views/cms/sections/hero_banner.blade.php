@php
    $heroClass = ($themeColorPreset['hero_class'] ?? null) ?: 'pp-hero-pattern';
@endphp
<section class="{{ $heroClass }} text-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
        <div class="max-w-3xl space-y-6">
            @if (filled($config['badge'] ?? null))
                <x-ui.badge tone="dark">{{ $config['badge'] }}</x-ui.badge>
            @endif

            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-5xl lg:leading-[1.12]">
                {{ $config['heading'] ?? '' }}
            </h1>

            @if (filled($config['subheading'] ?? null))
                <p class="text-base leading-relaxed text-emerald-50/90 sm:text-lg">
                    {{ $config['subheading'] }}
                </p>
            @endif

            <div class="flex flex-wrap items-center gap-3 pt-2">
                @if (filled($config['primary_cta_label'] ?? null) && filled($config['primary_cta_href'] ?? null))
                    <x-ui.button href="{{ $config['primary_cta_href'] }}" variant="accent" size="lg">
                        {{ $config['primary_cta_label'] }}
                    </x-ui.button>
                @endif
                @if (filled($config['secondary_cta_label'] ?? null) && filled($config['secondary_cta_href'] ?? null))
                    <x-ui.button href="{{ $config['secondary_cta_href'] }}" variant="outline-light" size="lg">
                        {{ $config['secondary_cta_label'] }}
                    </x-ui.button>
                @endif
            </div>

            @if (! empty($config['stats']))
                <dl class="grid grid-cols-2 gap-4 pt-6 border-t border-white/15 sm:grid-cols-3">
                    @foreach ($config['stats'] as $stat)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-emerald-200">{{ $stat['label'] ?? '' }}</dt>
                            <dd class="mt-1 text-lg font-bold text-white">{{ $stat['value'] ?? '' }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </div>
</section>
