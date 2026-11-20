<section class="{{ $styleClass }}">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8 space-y-3">
        @if (filled($config['eyebrow'] ?? null))
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-800">{{ $config['eyebrow'] }}</p>
        @endif
        @if (filled($config['heading'] ?? null))
            <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">{{ $config['heading'] }}</h2>
        @endif
        @if (filled($config['body_html'] ?? null))
            <div class="space-y-3 text-sm leading-relaxed text-slate-700 [&_h3]:font-bold [&_ul]:list-disc [&_ul]:pl-5">
                {!! $config['body_html'] !!}
            </div>
        @endif
    </div>
</section>
