<section class="{{ $styleClass }}">
    <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 lg:px-8 {{ ($config['alignment'] ?? 'left') === 'center' ? 'text-center' : '' }}">
        @if (filled($config['eyebrow'] ?? null))
            <p class="text-xs font-bold uppercase tracking-[0.18em] {{ $isDarkStyle ? 'text-emerald-300' : 'text-emerald-800' }}">{{ $config['eyebrow'] }}</p>
        @endif

        @if (filled($config['heading'] ?? null))
            <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl {{ $isDarkStyle ? 'text-white' : 'text-slate-900' }}">
                {{ $config['heading'] }}
            </h2>
        @endif

        @if (filled($config['body_html'] ?? null))
            <div class="mt-5 space-y-4 text-base leading-relaxed {{ $isDarkStyle ? 'text-slate-200' : 'text-slate-700' }} [&_h3]:text-lg [&_h3]:font-bold [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_a]:underline [&_a]:font-semibold">
                {!! $config['body_html'] !!}
            </div>
        @endif
    </div>
</section>
