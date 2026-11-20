@extends('layouts.public')

@php
    $meta = $version->meta_snapshot ?? [];
    $seoTitle = $page->seo_title ?: ($meta['seo_title'] ?? $page->title.' — '.($siteSettings['site_name'] ?? 'Precious Pakistan'));
    $seoDescription = $page->seo_description ?: ($meta['seo_description'] ?? ($siteSettings['site_tagline'] ?? ''));
    $canonicalUrl = $page->canonical_url ?: url()->current();
    $shouldNoindex = $isPreview || $page->noindex;
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)

@push('head_meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if ($shouldNoindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="website">
    @if (filled($page->og_image_url))
        <meta property="og:image" content="{{ $page->og_image_url }}">
    @endif
@endpush

@section('content')
    @if ($isPreview)
        <div class="border-b border-amber-300 bg-amber-100 text-amber-950">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-xs font-semibold sm:px-6 lg:px-8">
                <span>
                    Draft Preview Mode &mdash; Viewing Version v{{ $version->version_number }} ({{ ucfirst($version->status) }}).
                    Public visitors only see the published version.
                </span>
                <a href="/admin/cms-pages/{{ $page->id }}/edit" class="underline hover:text-amber-800">Return to Page Builder</a>
            </div>
        </div>
    @endif

    @foreach ($version->sections as $section)
        @if ($section->is_visible)
            @php
                $deviceClass = \App\Modules\Cms\Support\ThemePresets::deviceVisibilityClass($section->device_visibility);
                $styleClass = \App\Modules\Cms\Support\ThemePresets::sectionWrapperClass($section->style_preset);
                $viewName = 'cms.sections.'.$section->component_type;
            @endphp

            @if (view()->exists($viewName))
                <div class="{{ $deviceClass }}" data-cms-section="{{ $section->component_type }}">
                    @include($viewName, [
                        'section' => $section,
                        'config' => $section->config ?? [],
                        'styleClass' => $styleClass,
                        'isDarkStyle' => $section->style_preset === 'dark',
                    ])
                </div>
            @endif
        @endif
    @endforeach
@endsection
