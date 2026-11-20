<?php

use App\Modules\Cms\Http\Controllers\PublicPageController;
use App\Modules\Destinations\Http\Controllers\PublicDestinationController;
use App\Modules\Identity\Http\Controllers\CustomerDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');

Route::get('/destinations', [PublicDestinationController::class, 'index'])->name('destinations.index');
Route::get('/regions/{slug}', [PublicDestinationController::class, 'showRegion'])->name('regions.show');
Route::get('/destinations/{slug}', [PublicDestinationController::class, 'showDestination'])->name('destinations.show');
Route::get('/api/geo/features', [PublicDestinationController::class, 'geoJson'])
    ->middleware('throttle:api')
    ->name('geo.features');

Route::post('/cms/contact', [PublicPageController::class, 'submitContact'])
    ->middleware('throttle:cms-contact')
    ->name('cms.contact.submit');

Route::middleware(['auth', 'active', 'can:cms.view'])
    ->get('/cms/preview/{page}', [PublicPageController::class, 'preview'])
    ->name('cms.pages.preview');

Route::middleware(['auth', 'active', 'verified', 'customer'])
    ->group(function (): void {
        Route::get('/dashboard', CustomerDashboardController::class)->name('customer.dashboard');
    });

// Published dynamic CMS pages (e.g. /about, /contact, /terms, /privacy, /faqs).
// Explicitly excludes reserved application prefixes so domain, auth, admin, and API routes are never shadowed.
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '^(?!(?:admin|api|dashboard|destinations|regions|tours|login|logout|register|forgot-password|reset-password|verify-email|email|cms|livewire|storage|up)(?:/|$))[a-z0-9]+(?:-[a-z0-9]+)*$')
    ->name('cms.pages.show');
