@php
    $site = $siteSettings ?? [];
    $brandName = $site['site_name'] ?? config('precious.fallback_brand.name');
    $brandLogoUrl = $site['brand_logo_url'] ?? null;
    $tagline = $site['site_tagline'] ?? config('precious.fallback_brand.tagline');
    $supportEmail = $site['support_email'] ?? config('precious.fallback_brand.support_email');
    $operatingHours = $site['operating_hours'] ?? config('precious.fallback_brand.operating_hours');
    $announcementEnabled = (bool) ($site['announcement_enabled'] ?? false);
    $announcementText = $site['announcement_text'] ?? null;
    $footerAbout = $site['footer_about'] ?? $tagline;
    $footerCopyright = $site['footer_copyright'] ?? ($brandName.'. All rights reserved.');
    $socialLinks = is_array($site['social_links'] ?? null) ? $site['social_links'] : [];

    $colorPreset = $themeColorPreset ?? \App\Modules\Cms\Support\ThemePresets::resolveColorPreset($site['theme_color_preset'] ?? null);
    $typographyPreset = $themeTypographyPreset ?? \App\Modules\Cms\Support\ThemePresets::resolveTypographyPreset($site['theme_typography_preset'] ?? null);

    $fallbackNav = is_array($site['primary_nav'] ?? null) ? array_map(fn ($i) => [
        'label' => $i['label'] ?? '',
        'url' => $i['href'] ?? '#',
        'target' => '_self',
        'device_visibility' => 'all',
    ], $site['primary_nav']) : [];

    $headerMenu = ! empty($cmsMenus['header']) ? $cmsMenus['header'] : $fallbackNav;
    $mobileMenu = ! empty($cmsMenus['mobile']) ? $cmsMenus['mobile'] : $headerMenu;
    $footerPrimaryMenu = ! empty($cmsMenus['footer_primary']) ? $cmsMenus['footer_primary'] : $headerMenu;
    $footerLegalMenu = ! empty($cmsMenus['footer_legal']) ? $cmsMenus['footer_legal'] : [];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $brandName.' — '.$tagline)</title>
    <meta name="description" content="@yield('meta_description', $tagline)">
    @stack('head_meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased pb-16 md:pb-0 {{ $typographyPreset['body_class'] }}">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-amber-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Skip to main content
    </a>

    @if ($announcementEnabled && filled($announcementText))
        <div class="{{ $colorPreset['announcement_bg'] }} text-xs sm:text-sm">
            <div class="mx-auto max-w-7xl px-4 py-2 text-center sm:px-6 lg:px-8">
                {{ $announcementText }}
            </div>
        </div>
    @endif

    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                @if (filled($brandLogoUrl))
                    <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }} logo" class="h-10 w-auto max-w-[140px] object-contain">
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $colorPreset['primary_bg'] }} text-white font-bold tracking-tight shadow-xs" aria-hidden="true">
                        PP
                    </span>
                @endif
                <span class="flex flex-col">
                    <span class="text-base font-bold tracking-tight text-slate-900 leading-tight">{{ $brandName }}</span>
                    <span class="hidden sm:block text-[11px] font-medium text-emerald-800 leading-tight">Pakistan Tourism Platform</span>
                </span>
            </a>

            <nav aria-label="Primary navigation" class="hidden md:flex items-center gap-6">
                @foreach ($headerMenu as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        target="{{ $item['target'] ?? '_self' }}"
                        @if (($item['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif
                        class="text-sm font-semibold text-slate-700 hover:text-emerald-800 transition-colors {{ \App\Modules\Cms\Support\ThemePresets::deviceVisibilityClass($item['device_visibility'] ?? 'all') }}"
                    >
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
                @foreach ($mobileMenu as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        target="{{ $item['target'] ?? '_self' }}"
                        @if (($item['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif
                        class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-800 hover:bg-emerald-50 hover:text-emerald-900"
                    >
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
            <div class="grid grid-cols-1 gap-10 md:grid-cols-4">
                <div class="space-y-3 md:col-span-2">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-white font-bold text-sm" aria-hidden="true">PP</span>
                        <span class="text-lg font-bold text-white">{{ $brandName }}</span>
                    </div>
                    <p class="max-w-md text-sm leading-relaxed text-slate-400">{{ $footerAbout }}</p>

                    @if (! empty($socialLinks))
                        <div class="flex flex-wrap items-center gap-2 pt-2">
                            @foreach ($socialLinks as $social)
                                <a
                                    href="{{ $social['url'] ?? '#' }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center rounded-lg border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:border-emerald-600 hover:text-white transition-colors"
                                >
                                    {{ $social['label'] ?? ucfirst((string) ($social['platform'] ?? 'Social')) }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Explore</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($footerPrimaryMenu as $item)
                            <li>
                                <a href="{{ $item['url'] ?? '#' }}" class="hover:text-white transition-colors">{{ $item['label'] ?? '' }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Operations &amp; Policies</h2>
                    <p class="text-sm text-slate-300">Email: <a href="mailto:{{ $supportEmail }}" class="underline hover:text-white">{{ $supportEmail }}</a></p>
                    <p class="text-xs text-slate-400">Hours: {{ $operatingHours }}</p>
                    @if (! empty($footerLegalMenu))
                        <ul class="space-y-1.5 pt-2 text-sm">
                            @foreach ($footerLegalMenu as $item)
                                <li>
                                    <a href="{{ $item['url'] ?? '#' }}" class="hover:text-white transition-colors">{{ $item['label'] ?? '' }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="mt-10 border-t border-slate-800 pt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-400">
                <p>&copy; {{ now()->year }} {{ $footerCopyright }}</p>
                <p>Display Timezone: {{ \App\Shared\Support\DisplayTimezone::timezone() }} &bull; PKR &amp; USD Transparent Quotations</p>
            </div>
        </div>
    </footer>

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
                <x-ui.button href="/contact" variant="accent" size="sm">
                    Inquire Now
                </x-ui.button>
            @endauth
        </div>
    </div>

    @livewireScripts
</body>
</html>
