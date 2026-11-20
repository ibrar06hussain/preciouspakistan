<section class="{{ $styleClass }}">
    <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="$config['eyebrow'] ?? null"
            :title="$config['heading'] ?? 'Frequently Asked Questions'"
            :subtitle="$config['subtitle'] ?? null"
        />

        <div class="mt-8 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
            @foreach ($config['items'] ?? [] as $item)
                <details class="group p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-slate-900">
                        <span>{{ $item['question'] ?? '' }}</span>
                        <span class="text-emerald-800 transition-transform group-open:rotate-45 text-xl leading-none" aria-hidden="true">+</span>
                    </summary>
                    <div class="mt-3 text-sm leading-relaxed text-slate-700 space-y-2">
                        {!! $item['answer_html'] ?? '' !!}
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</section>
