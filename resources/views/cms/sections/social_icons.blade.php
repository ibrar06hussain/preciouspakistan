<section class="{{ $styleClass }}">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8 text-center space-y-4">
        @if (filled($config['heading'] ?? null))
            <h2 class="text-xl font-bold text-slate-900">{{ $config['heading'] }}</h2>
        @endif
        @if (filled($config['subtitle'] ?? null))
            <p class="text-sm text-slate-600">{{ $config['subtitle'] }}</p>
        @endif
        <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
            @foreach ($config['links'] ?? [] as $link)
                <a
                    href="{{ $link['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:border-emerald-700 hover:text-emerald-900 shadow-xs transition"
                >
                    {{ $link['label'] ?? ucfirst((string) ($link['platform'] ?? 'Link')) }}
                </a>
            @endforeach
        </div>
    </div>
</section>
