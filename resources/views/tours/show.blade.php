@extends('layouts.public')

@section('title', $tour->seo_title ?: $tour->title.' — Precious Pakistan')
@section('meta_description', $tour->seo_description ?: $tour->summary)

@push('head_meta')
    <link rel="canonical" href="{{ $tour->canonical_url ?: $tour->publicUrl() }}">
    @if ($tour->noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
@endpush

@section('content')
    <section class="pp-hero-pattern text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 space-y-4">
            <nav aria-label="Breadcrumb" class="text-xs font-medium text-emerald-200 flex flex-wrap items-center gap-2">
                <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('tours.index') }}" class="hover:text-white">Tours</a>
                <span aria-hidden="true">/</span>
                <span class="text-white">{{ $tour->title }}</span>
            </nav>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge tone="dark">{{ \App\Modules\Tours\Models\Tour::CATEGORIES[$tour->category] ?? ucfirst($tour->category) }}</x-ui.badge>
                <x-ui.badge tone="dark">{{ $tour->code }}</x-ui.badge>
                @if ($tour->is_sample_content)
                    <x-ui.badge tone="demo">Sample Tour Package</x-ui.badge>
                @endif
                @if ($tour->supports_nomad_addon)
                    <x-ui.badge tone="amber">Digital Nomad Eligible</x-ui.badge>
                @endif
            </div>

            <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $tour->title }}</h1>
            <p class="max-w-3xl text-sm leading-relaxed text-emerald-50/90 sm:text-base">{{ $tour->summary }}</p>

            <dl class="grid grid-cols-2 gap-4 pt-4 border-t border-white/15 sm:grid-cols-4 max-w-3xl">
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-emerald-200">Duration</dt>
                    <dd class="mt-0.5 text-sm font-bold text-white">{{ $tour->duration_days }} Days / {{ $tour->duration_nights }} Nights</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-emerald-200">Group Size</dt>
                    <dd class="mt-0.5 text-sm font-bold text-white">{{ $tour->min_guests }}–{{ $tour->max_guests }} Travelers</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-emerald-200">Difficulty</dt>
                    <dd class="mt-0.5 text-sm font-bold text-white">{{ \App\Modules\Tours\Models\Tour::DIFFICULTIES[$tour->difficulty] ?? ucfirst($tour->difficulty) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-emerald-200">Pickup / Dropoff</dt>
                    <dd class="mt-0.5 text-xs font-semibold text-white">{{ $tour->pickup_location ?: 'Islamabad' }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 grid grid-cols-1 gap-8 lg:grid-cols-12">
        <div class="lg:col-span-8 space-y-8">
            {{-- Overview & Highlights --}}
            <x-ui.card class="space-y-5">
                <h2 class="text-2xl font-bold text-slate-900">Tour Overview</h2>
                <div class="space-y-3 text-sm leading-relaxed text-slate-700 [&_h3]:text-base [&_h3]:font-bold [&_ul]:list-disc [&_ul]:pl-5">
                    {!! $tour->description_html !!}
                </div>

                @if (! empty($tour->highlights))
                    <div class="border-t border-slate-100 pt-4 space-y-2">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-emerald-800">Key Highlights</h3>
                        <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2 text-sm text-slate-700">
                            @foreach ($tour->highlights as $highlight)
                                <li class="flex items-start gap-2">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-700" aria-hidden="true"></span>
                                    <span>{{ $highlight }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-ui.card>

            {{-- Chronological Day-by-Day Itinerary --}}
            <div class="space-y-4">
                <h2 class="text-2xl font-bold text-slate-900">Day-by-Day Itinerary</h2>
                <div class="space-y-4">
                    @foreach ($tour->itineraryDays as $day)
                        <x-ui.card class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-800 text-xs font-bold text-white">
                                        D{{ $day->day_number }}
                                    </span>
                                    <h3 class="text-lg font-bold text-slate-900">{{ $day->title }}</h3>
                                </div>
                                @if ($day->destination)
                                    <a href="{{ $day->destination->publicUrl() }}" class="text-xs font-semibold text-emerald-800 hover:underline">
                                        {{ $day->destination->name }}
                                    </a>
                                @endif
                            </div>

                            @if (filled($day->description_html))
                                <div class="text-sm leading-relaxed text-slate-700 space-y-2">
                                    {!! $day->description_html !!}
                                </div>
                            @endif

                            <div class="flex flex-wrap gap-3 text-xs text-slate-600 pt-1">
                                @if ($day->meals_included)
                                    <span><strong>Meals:</strong> {{ $day->meals_included }}</span>
                                @endif
                                @if ($day->travel_distance_km)
                                    <span><strong>Drive:</strong> ~{{ $day->travel_distance_km }} km</span>
                                @endif
                                @if ($day->altitude_meters)
                                    <span><strong>Elevation:</strong> ~{{ number_format($day->altitude_meters) }} m</span>
                                @endif
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>

            {{-- Overnight Tour Stays --}}
            @if ($tour->stays->isNotEmpty())
                <x-ui.card class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-xl font-bold text-slate-900">Overnight Stops</h2>
                        <x-ui.badge tone="slate">Tour-Only Verified Stays</x-ui.badge>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($tour->stays as $stay)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                                <p class="text-sm font-bold text-slate-900">{{ $stay->destination?->name }}</p>
                                <p class="text-xs font-semibold text-emerald-800">
                                    Check-in Day {{ $stay->check_in_day }} &bull; {{ $stay->nights_count }} {{ \Illuminate\Support\Str::plural('night', $stay->nights_count) }}
                                </p>
                                @if ($stay->notes)
                                    <p class="text-xs text-slate-600">{{ $stay->notes }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

            {{-- Inclusions & Exclusions --}}
            @if (! empty($tour->inclusions) || ! empty($tour->exclusions))
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <x-ui.card class="space-y-3">
                        <h2 class="text-lg font-bold text-emerald-900">What’s Included</h2>
                        <ul class="space-y-2 text-sm text-slate-700">
                            @foreach ($tour->inclusions ?? [] as $inc)
                                <li>&check; {{ $inc }}</li>
                            @endforeach
                        </ul>
                    </x-ui.card>

                    <x-ui.card class="space-y-3">
                        <h2 class="text-lg font-bold text-slate-900">Not Included</h2>
                        <ul class="space-y-2 text-sm text-slate-600">
                            @foreach ($tour->exclusions ?? [] as $exc)
                                <li>&times; {{ $exc }}</li>
                            @endforeach
                        </ul>
                    </x-ui.card>
                </div>
            @endif

            {{-- FAQs --}}
            @if (! empty($tour->faqs))
                <div class="space-y-3">
                    <h2 class="text-2xl font-bold text-slate-900">Tour FAQs</h2>
                    <div class="divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                        @foreach ($tour->faqs as $faq)
                            <details class="group p-5">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-slate-900">
                                    <span>{{ $faq['question'] ?? '' }}</span>
                                    <span class="text-emerald-800 text-xl leading-none" aria-hidden="true">+</span>
                                </summary>
                                <div class="mt-3 text-sm leading-relaxed text-slate-700">
                                    {!! $faq['answer_html'] ?? '' !!}
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar: Pricing, Date/Guest Selector & Booking Entry --}}
        <aside class="lg:col-span-4 space-y-6">
            <x-ui.card class="sticky top-20 space-y-5">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Transparent Pricing</span>
                    <p class="text-2xl font-extrabold text-slate-900">{{ $tour->displayPrice('PKR') }}</p>
                    @if ($tour->pricing_model !== \App\Modules\Tours\Models\Tour::PRICING_CUSTOM_QUOTE && $tour->base_price_usd)
                        <p class="text-xs font-semibold text-slate-600">{{ $tour->displayPrice('USD') }}</p>
                    @endif
                </div>

                @if (session('booking_entry_status'))
                    <x-ui.alert type="success">{{ session('booking_entry_status') }}</x-ui.alert>
                @endif

                @error('booking_entry')
                    <x-ui.alert type="error">{{ $message }}</x-ui.alert>
                @enderror

                <form method="POST" action="{{ route('tours.booking-entry', ['slug' => $tour->slug]) }}" class="space-y-4">
                    @csrf

                    @if ($tour->openDepartures->isNotEmpty())
                        <div class="space-y-1.5">
                            <label for="departure-id" class="block text-sm font-semibold text-slate-900">Scheduled Departure (Optional)</label>
                            <select id="departure-id" name="departure_id" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                                <option value="">Custom Private Start Date</option>
                                @foreach ($tour->openDepartures as $dep)
                                    <option value="{{ $dep->id }}" @selected((int) old('departure_id') === $dep->id)>
                                        {{ $dep->start_date->format('d M Y') }} ({{ $dep->remainingSeats() }} seats open)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <x-ui.input
                        label="Preferred Start Date"
                        name="start_date"
                        type="date"
                        :value="now()->addDays(max(7, $tour->booking_lead_days))->toDateString()"
                        hint="Advance lead time: at least {{ $tour->booking_lead_days }} day(s)."
                    />

                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input
                            label="Travelers"
                            name="guests"
                            type="number"
                            :value="$tour->min_guests"
                            min="{{ $tour->min_guests }}"
                            max="{{ $tour->max_guests }}"
                            required
                        />

                        <div class="space-y-1.5">
                            <label for="quote-currency" class="block text-sm font-semibold text-slate-900">Currency</label>
                            <select id="quote-currency" name="currency" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900">
                                <option value="PKR">PKR</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>

                    <x-ui.button type="submit" variant="primary" size="lg" class="w-full">
                        Check Dates &amp; Continue
                    </x-ui.button>
                </form>

                <div class="border-t border-slate-100 pt-4 space-y-2 text-xs text-slate-600">
                    <p>&bull; Company fleet options include professional driver at zero extra cost.</p>
                    <p>&bull; Preferred tour hotel availability is verified externally by our booking officers before confirmation.</p>
                    <x-ui.button href="/contact" variant="outline" size="sm" class="w-full mt-2">
                        Ask a Booking Officer
                    </x-ui.button>
                </div>
            </x-ui.card>
        </aside>
    </section>
@endsection
