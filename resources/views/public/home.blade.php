@extends('layouts.public')

@php
    $site = $site ?? $siteSettings ?? [];
    $brandName = $site['site_name'] ?? config('precious.fallback_brand.name');
    $heroBadge = $site['hero_badge'] ?? 'Company-Operated Pakistan Tourism Platform';
    $heroTitle = $site['hero_title'] ?? 'Discover Pakistan with Precision, Comfort & Local Mastery';
    $heroSubtitle = $site['hero_subtitle'] ?? 'Company-managed journeys across every region of Pakistan with driver-included transport, verified tour stays, and transparent PKR/USD quotations.';
    $primaryCtaLabel = $site['hero_primary_cta_label'] ?? 'Create Traveler Account';
    $primaryCtaHref = $site['hero_primary_cta_href'] ?? route('register');
    $secondaryCtaLabel = $site['hero_secondary_cta_label'] ?? 'Explore Regions';
    $secondaryCtaHref = $site['hero_secondary_cta_href'] ?? '#regions';
    $regions = is_array($site['regions_preview'] ?? null) ? $site['regions_preview'] : [];
    $sampleJourneys = is_array($site['sample_journeys'] ?? null) ? $site['sample_journeys'] : [];
@endphp

@section('title', $brandName.' — Curated Pakistan Tourism Platform')

@section('content')
    {{-- Hero Section --}}
    <section class="pp-hero-pattern text-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-28">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12">
                <div class="lg:col-span-7 space-y-6">
                    <x-ui.badge tone="dark">{{ $heroBadge }}</x-ui.badge>

                    <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-[3.25rem] lg:leading-[1.12]">
                        {{ $heroTitle }}
                    </h1>

                    <p class="max-w-2xl text-base leading-relaxed text-emerald-50/90 sm:text-lg">
                        {{ $heroSubtitle }}
                    </p>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <x-ui.button href="{{ $primaryCtaHref }}" variant="accent" size="lg">
                            {{ $primaryCtaLabel }}
                        </x-ui.button>
                        <x-ui.button href="{{ $secondaryCtaHref }}" variant="outline-light" size="lg">
                            {{ $secondaryCtaLabel }}
                        </x-ui.button>
                    </div>

                    <dl class="grid grid-cols-2 gap-4 pt-6 border-t border-white/15 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-emerald-200">Coverage</dt>
                            <dd class="mt-1 text-lg font-bold text-white">All 7 Regions</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-emerald-200">Company Fleet</dt>
                            <dd class="mt-1 text-lg font-bold text-white">Driver Included</dd>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <dt class="text-xs font-medium uppercase tracking-wider text-emerald-200">Billing</dt>
                            <dd class="mt-1 text-lg font-bold text-white">PKR &amp; USD Invoices</dd>
                        </div>
                    </dl>
                </div>

                <div class="lg:col-span-5">
                    <div class="rounded-3xl border border-white/15 bg-white/10 p-6 sm:p-8 backdrop-blur-md shadow-2xl space-y-5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold uppercase tracking-widest text-amber-300">How Precious Pakistan Works</span>
                            <x-ui.badge tone="dark">Transparent Flow</x-ui.badge>
                        </div>

                        <ol class="space-y-4 text-sm text-emerald-50">
                            <li class="flex gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-500 text-slate-950 font-bold text-xs">1</span>
                                <div>
                                    <p class="font-semibold text-white">Choose Destinations &amp; Tour Package</p>
                                    <p class="text-emerald-100/80 text-xs mt-0.5">Select custom itineraries across northern peaks, valleys, heritage cities, or coast.</p>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-500 text-slate-950 font-bold text-xs">2</span>
                                <div>
                                    <p class="font-semibold text-white">Pick Company Fleet, Fuel Mode &amp; Tour Stays</p>
                                    <p class="text-emerald-100/80 text-xs mt-0.5">Drivers are always included. Choose with-fuel or without-fuel seasonal daily rates plus preferred hotel stops.</p>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-500 text-slate-950 font-bold text-xs">3</span>
                                <div>
                                    <p class="font-semibold text-white">Booking Officer Verification &amp; Immutable Quote</p>
                                    <p class="text-emerald-100/80 text-xs mt-0.5">Our team verifies stay availability externally before confirming your locked PKR or USD invoice.</p>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- All-Pakistan Regions Overview --}}
    <section id="regions" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <x-ui.section-heading
            eyebrow="Nationwide Coverage"
            title="All Seven Regions of Pakistan"
            subtitle="Designed from the ground up for authentic, region-by-region exploration — from Karakoram glaciers to Indus heritage and Makran coast."
        />

        <div class="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($regions as $region)
                <x-ui.card class="flex flex-col justify-between hover:border-emerald-300 hover:shadow-md transition">
                    <div class="space-y-2.5">
                        <x-ui.badge tone="emerald">{{ $region['tag'] ?? 'Pakistan' }}</x-ui.badge>
                        <h3 class="text-lg font-bold text-slate-900">{{ $region['name'] ?? '' }}</h3>
                        <p class="text-sm leading-relaxed text-slate-600">{{ $region['highlights'] ?? '' }}</p>
                    </div>
                    <p class="mt-5 text-xs font-semibold text-emerald-800">
                        Interactive maps &amp; destination guides arriving in Phase 4
                    </p>
                </x-ui.card>
            @endforeach
        </div>
    </section>

    {{-- Sample Demo Journeys --}}
    <section id="sample-journeys" class="border-y border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <x-ui.section-heading
                    eyebrow="Sample Preview Catalog"
                    title="Signature Journeys & Nomad Retreats"
                    subtitle="Illustrative sample journeys demonstrating Precious Pakistan's transparent quote-first pricing model."
                />
                <x-ui.badge tone="demo">Sample / Demo Content</x-ui.badge>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ($sampleJourneys as $journey)
                    <x-ui.card class="flex flex-col justify-between bg-slate-50/70">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <x-ui.badge tone="demo">Sample Preview</x-ui.badge>
                                <span class="text-xs font-semibold text-slate-600">{{ $journey['duration'] ?? '' }}</span>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">{{ $journey['title'] ?? '' }}</h3>
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">{{ $journey['region'] ?? '' }}</p>
                            <p class="text-sm leading-relaxed text-slate-600">{{ $journey['summary'] ?? '' }}</p>
                        </div>

                        <div class="mt-6 flex items-center justify-between border-t border-slate-200 pt-4">
                            <div>
                                <span class="block text-[11px] font-medium uppercase tracking-wider text-slate-500">Pricing</span>
                                <span class="text-sm font-bold text-slate-900">{{ $journey['price_display'] ?? 'Request Quote' }}</span>
                            </div>
                            <x-ui.button href="{{ route('register') }}" variant="outline" size="sm">
                                Request Quote
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Company Standards & Digital Nomad Preview --}}
    <section id="company-standards" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <x-ui.card class="space-y-4">
                <x-ui.badge tone="emerald">Company-Operated Standards</x-ui.badge>
                <h2 class="text-2xl font-bold text-slate-900">Fleet with Included Drivers &amp; Verified Tour Stays</h2>
                <p class="text-sm leading-relaxed text-slate-600">
                    Every company vehicle includes a professional driver in its daily rate — never double-charged. Select transparent with-fuel or without-fuel seasonal rates, pair preferred overnight hotel options exclusively within your tour itinerary, and receive confirmed availability verified directly by our booking officers.
                </p>
            </x-ui.card>

            <x-ui.card id="nomad-preview" :dark="true" class="space-y-4">
                <x-ui.badge tone="dark">Digital Nomad Add-On</x-ui.badge>
                <h2 class="text-2xl font-bold text-white">Work Remotely From Pakistan's Mountain Valleys</h2>
                <p class="text-sm leading-relaxed text-emerald-100/90">
                    Attach dedicated coworking desks, private meeting rooms, and verified backup-power workspace days directly to your tour package — always with honest internet speed and power-continuity disclosures.
                </p>
            </x-ui.card>
        </div>
    </section>
@endsection
