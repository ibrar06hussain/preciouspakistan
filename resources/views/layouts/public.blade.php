@php
    $site = $siteSettings ?? [];
    $brandName = $site['site_name'] ?? config('precious.fallback_brand.name');
    $tagline = $site['site_tagline'] ?? config('precious.fallback_brand.tagline');
    $supportEmail = $site['support_email'] ?? config('precious.fallback_brand.support_email');
    $operatingHours = $site['operating_hours'] ?? config('precious.fallback_brand.operating_hours');
    $announcementEnabled = (bool) ($site['announcement_enabled'] ?? false);
    $announcementText = $site['announcement_text'] ?? null;
    $primaryNav = is_array($site['primary_nav'] ?? null) ? $site['primary_nav'] : [];
    $footerAbout = $site['footer_about'] ?? $tagline;
    $footerCopyright = $site['footer_copyright'] ?? ($brandName.'. All rights reserved.');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $brandName.' — '.$tagline)</title>
    <meta name="description" content="@yield('meta_description', $tagline)">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased pb-16 md:pb-0">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-amber-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Skip to main content
    </a>

    @if ($announcementEnabled && filled($announcementText))
        <div class="bg-emerald-950 text-emerald-50 text-xs sm:text-sm">
            <div class="mx-auto max-w-7xl px-4 py-2 text-center sm:px-6 lg:px-8">
                {{ $announcementText }}
            </div>
        </div>
    @endif

    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-800 text-white font-bold tracking-tight shadow-xs group-hover:bg-emerald-900 transition-colors" aria-hidden="true">
                    PP
                </span>
                <span class="flex flex-col">
                    <span class="text-base font-bold tracking-tight text-slate-900 leading-tight">{{ $brandName }}</span>
                    <span class="hidden sm:block text-[11px] font-medium text-emerald-800 leading-tight">Pakistan Tourism Platform</span>
                </span>
            </a>

            <nav aria-label="Primary navigation" class="hidden md:flex items-center gap-6">
                @foreach ($primaryNav as $item)
                    <a href="{{ $item['href'] ?? '#' }}" class="text-sm font-semibold text-slate-700 hover:text-emerald-800 transition-colors">
                        {{ $item['label'] ?? '' }}
                    </a>
                @endforeach
            </nav>

            <div class="hidden md:flex items-center gap-3">
                @auth
                    @if (auth()->user()->isStaff() && auth()->user()->hasVerifiedEmail() && auth()->user()->hasStaffRole())
                        <x-ui.button href="/admin" variant="secondary" size="sm">Admin Portal</x-ui.button>
                    @elseif (auth()->user()->isCustomer())
                        <x-ui.button href="{{ route('customer.dashboard') }}" variant="outline" size="sm">My Dashboard</x-ui.button>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" size="sm">Sign Out</x-ui.button>
                    </form>
                @else
                    <x-ui.button href="{{ route('login') }}" variant="ghost" size="sm">Sign In</x-ui.button>
                    <x-ui.button href="{{ route('register') }}" variant="primary" size="sm">Plan Your Trip</x-ui.button>
                @endauth
            </div>

            <button
                type="button"
                data-mobile-nav-toggle
                aria-expanded="false"
                aria-controls="mobile-navigation-drawer"
                aria-label="Toggle navigation menu"
                class="md:hidden inline-flex items-center justify-center rounded-lg border border-slate-300 p-2 text-slate-800 hover:bg-slate-100"
            >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <nav
            id="mobile-navigation-drawer"
            aria-label="Mobile navigation"
            class="hidden md:hidden border-t border-slate-200 bg-white px-4 py-4 space-y-3 shadow-lg"
        >
            <div class="space-y-1">
                @foreach ($primaryNav as $item)
                    <a href="{{ $item['href'] ?? '#' }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 hover:bg-emerald-50 hover:text-emerald-900">
                        {{ $item['label'] ?? '' }}
                    </a>
                @endforeach
            </div>

            <div class="border-t border-slate-200 pt-3 flex flex-col gap-2">
                @auth
                    @if (auth()->user()->isStaff() && auth()->user()->hasVerifiedEmail() && auth()->user()->hasStaffRole())
                        <x-ui.button href="/admin" variant="secondary" class="w-full">Admin Portal</x-ui.button>
                    @elseif (auth()->user()->isCustomer())
                        <x-ui.button href="{{ route('customer.dashboard') }}" variant="outline" class="w-full">My Dashboard</x-ui.button>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" class="w-full">Sign Out</x-ui.button>
                    </form>
                @else
                    <x-ui.button href="{{ route('login') }}" variant="outline" class="w-full">Sign In</x-ui.button>
                    <x-ui.button href="{{ route('register') }}" variant="primary" class="w-full">Create Traveler Account</x-ui.button>
                @endauth
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-slate-950 text-slate-300 mt-16">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
                <div class="space-y-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-white font-bold text-sm" aria-hidden="true">PP</span>
                        <span class="text-lg font-bold text-white">{{ $brandName }}</span>
                    </div>
                    <p class="text-sm leading-relaxed text-slate-400">{{ $footerAbout }}</p>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Navigation</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($primaryNav as $item)
                            <li>
                                <a href="{{ $item['href'] ?? '#' }}" class="hover:text-white transition-colors">{{ $item['label'] ?? '' }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Operations & Support</h2>
                    <p class="text-sm text-slate-300">Email: <a href="mailto:{{ $supportEmail }}" class="underline hover:text-white">{{ $supportEmail }}</a></p>
                    <p class="text-sm text-slate-400">Hours: {{ $operatingHours }}</p>
                    <p class="text-xs text-slate-500">All displayed times use {{ \App\Shared\Support\DisplayTimezone::timezone() }} (stored in UTC).</p>
                </div>
            </div>

            <div class="mt-10 border-t border-slate-800 pt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-400">
                <p>&copy; {{ now()->year }} {{ $footerCopyright }}</p>
                <p>Company-Operated Tourism Platform &bull; PKR &amp; USD Transparent Quotations</p>
            </div>
        </div>
    </footer>

    {{-- Sticky Mobile CTA Bar --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 backdrop-blur px-4 py-2.5 shadow-lg md:hidden">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-xs font-bold text-slate-900">{{ $brandName }}</p>
                <p class="truncate text-[11px] text-slate-600">Custom Pakistan Tours &amp; Fleet</p>
            </div>
            @auth
                <x-ui.button href="{{ auth()->user()->isStaff() ? '/admin' : route('customer.dashboard') }}" variant="primary" size="sm">
                    Dashboard
                </x-ui.button>
            @else
                <x-ui.button href="{{ route('register') }}" variant="accent" size="sm">
                    Plan Trip
                </x-ui.button>
            @endauth
        </div>
    </div>

    @livewireScripts
</body>
</html>
