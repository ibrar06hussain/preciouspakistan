@php
    $colsClass = match ((int) ($config['columns'] ?? 3)) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
@endphp
<section class="{{ $styleClass }}">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="$config['eyebrow'] ?? null"
            :title="$config['heading'] ?? ''"
            :subtitle="$config['subtitle'] ?? null"
        />

        <div class="mt-8 grid grid-cols-1 gap-6 {{ $colsClass }}">
            @foreach ($config['cards'] ?? [] as $card)
                <x-ui.card :dark="$isDarkStyle" class="flex flex-col justify-between space-y-4">
                    <div class="space-y-2.5">
                        @if (filled($card['badge'] ?? null))
                            <x-ui.badge :tone="($card['is_sample'] ?? false) ? 'demo' : 'emerald'">{{ $card['badge'] }}</x-ui.badge>
                        @endif
                        <h3 class="text-lg font-bold {{ $isDarkStyle ? 'text-white' : 'text-slate-900' }}">{{ $card['title'] ?? '' }}</h3>
                        <p class="text-sm leading-relaxed {{ $isDarkStyle ? 'text-emerald-100/90' : 'text-slate-600' }}">{{ $card['body'] ?? '' }}</p>
                    </div>
                    @if (filled($card['cta_label'] ?? null) && filled($card['cta_href'] ?? null))
                        <div>
                            <x-ui.button href="{{ $card['cta_href'] }}" variant="outline" size="sm">{{ $card['cta_label'] }}</x-ui.button>
                        </div>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    </div>
</section>
