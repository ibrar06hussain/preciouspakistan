<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Cms\Services\PageRendererService;
use App\Modules\Cms\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function home(PageRendererService $renderer, SiteSettingsService $settings): View
    {
        $resolved = $renderer->resolvePublishedHomepage();

        if ($resolved === null) {
            return view('public.home', [
                'site' => $settings->allPublic(),
            ]);
        }

        return view('cms.page', [
            'page' => $resolved['page'],
            'version' => $resolved['version'],
            'isPreview' => false,
        ]);
    }

    public function show(string $slug, PageRendererService $renderer): View
    {
        $resolved = $renderer->resolvePublishedBySlug($slug);

        if ($resolved === null) {
            abort(404);
        }

        return view('cms.page', [
            'page' => $resolved['page'],
            'version' => $resolved['version'],
            'isPreview' => false,
        ]);
    }

    public function preview(CmsPage $page, PageRendererService $renderer): Response
    {
        $resolved = $renderer->resolveDraftForPreview($page);

        if ($resolved === null) {
            abort(404, 'No version exists yet for this page.');
        }

        return response()
            ->view('cms.page', [
                'page' => $resolved['page'],
                'version' => $resolved['version'],
                'isPreview' => true,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        // Basic honeypot anti-spam trap.
        if (filled($request->input('website_url'))) {
            return back()->with('contact_status', 'Thank you. Your inquiry has been received.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:32'],
            'preferred_dates' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'consent' => ['accepted'],
        ]);

        Log::info('Precious Pakistan CMS contact inquiry received', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'has_phone' => filled($validated['phone'] ?? null),
            'preferred_dates' => $validated['preferred_dates'] ?? null,
        ]);

        return back()->with(
            'contact_status',
            'Thank you! Your inquiry has been received by Precious Pakistan. A booking officer will reply during operating hours (Asia/Karachi).'
        );
    }
}
