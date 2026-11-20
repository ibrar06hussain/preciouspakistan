@extends('layouts.public')

@section('title', 'Digital Nomad Tour Add-Ons — Precious Pakistan')
@section('meta_description', 'Attach coworking desks, meeting rooms, and transparently disclosed backup-power workspaces to eligible Precious Pakistan tours.')

@push('head_meta')
    <link rel="canonical" href="{{ route('nomads.index') }}">
@endpush

@section('content')
    <section class="pp-hero-pattern text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 space-y-4">
            <x-ui.badge tone="amber">Tour Add-On Only &mdash; Standalone Workspace Checkout Disabled</x-ui.badge>
            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
                Digital Nomad Workspaces in Pakistan
            </h1>
            <p class="max-w-3xl text-sm leading-relaxed text-emerald-50/90 sm:text-base">
                Combine guided northern expeditions with reserved coworking desks, private offices, or meeting rooms — always with honest power-backup and ISP disclosures.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 space-y-10">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @foreach ($locations as $loc)
                <x-ui.card class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <x-ui.badge tone="emerald">{{ $loc->destination?->name }}</x-ui.badge>
                        @if ($loc->is_sample_content)
                            <x-ui.badge tone="demo">Sample Workspace</x-ui.badge>
                        @endif
                    </div>

                    <h2 class="text-xl font-bold text-slate-900">{{ $loc->name }}</h2>

                    <dl class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs">
                        <div>
                            <dt class="font-bold uppercase tracking-wider text-slate-500">Internet / ISP</dt>
                            <dd class="mt-0.5 text-slate-900 font-semibold">
                                {{ $loc->internet_provider ?: 'Local ISP' }}
                                @if ($loc->advertised_download_mbps) ({{ $loc->advertised_download_mbps }} Mbps down / {{ $loc->advertised_upload_mbps ?? '—' }} up) @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="font-bold uppercase tracking-wider text-slate-500">Power &amp; Backup</dt>
                            <dd class="mt-0.5 text-slate-900 font-semibold">{{ $loc->backup_power_details ?: $loc->power_source ?: 'Backup disclosed on request' }}</dd>
                        </div>
                    </dl>

                    <div class="rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-950">
                        <strong>Connectivity &amp; Power Disclosure:</strong> {{ $loc->honestyNotice() }}
                    </div>

                    @if ($loc->facilities->isNotEmpty())
                        <ul class="space-y-1.5 text-xs text-slate-700 border-t border-slate-100 pt-3">
                            @foreach ($loc->facilities as $fac)
                                @php $rate = $fac->rates->first(); @endphp
                                <li>
                                    <strong>{{ $fac->name }}</strong> ({{ $fac->seat_capacity }} capacity)
                                    @if ($rate?->daily_rate_pkr) &mdash; PKR {{ number_format((float) $rate->daily_rate_pkr, 0) }}/day @endif
                                    @if ($rate?->hourly_rate_pkr) &bull; PKR {{ number_format((float) $rate->hourly_rate_pkr, 0) }}/hr @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            @endforeach
        </div>

        @if ($nomadTours->isNotEmpty())
            <div class="space-y-4">
                <h2 class="text-2xl font-bold text-slate-900">Eligible Nomad-Ready Tour Packages</h2>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    @foreach ($nomadTours as $tour)
                        <x-ui.card class="flex flex-col justify-between space-y-3">
                            <div>
                                <x-ui.badge tone="amber">Nomad Add-On Enabled</x-ui.badge>
                                <h3 class="mt-2 text-lg font-bold text-slate-900">{{ $tour->title }}</h3>
                                <p class="mt-1 text-xs text-slate-600">{{ $tour->duration_days }}D/{{ $tour->duration_nights }}N &bull; {{ $tour->displayPrice('PKR') }}</p>
                            </div>
                            <x-ui.button href="{{ $tour->publicUrl() }}" variant="primary" size="sm">View Tour &amp; Add Workspace</x-ui.button>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection
