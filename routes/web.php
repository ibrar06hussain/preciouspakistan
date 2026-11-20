<?php

use App\Modules\Booking\Http\Controllers\PublicBookingController;
use App\Modules\Cms\Http\Controllers\PublicPageController;
use App\Modules\Destinations\Http\Controllers\PublicDestinationController;
use App\Modules\Finance\Http\Controllers\InvoicePublicController;
use App\Modules\Fleet\Http\Controllers\PublicFleetController;
use App\Modules\Hotels\Http\Controllers\PublicHotelInfoController;
use App\Modules\Identity\Http\Controllers\CustomerDashboardController;
use App\Modules\Media\Http\Controllers\JoditMediaController;
use App\Modules\Nomads\Http\Controllers\PublicNomadController;
use App\Modules\Seo\Http\Controllers\SeoPublicController;
use App\Modules\Tours\Http\Controllers\PublicTourController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');

Route::get('/destinations', [PublicDestinationController::class, 'index'])->name('destinations.index');
Route::get('/regions/{slug}', [PublicDestinationController::class, 'showRegion'])->name('regions.show');
Route::get('/destinations/{slug}', [PublicDestinationController::class, 'showDestination'])->name('destinations.show');
Route::get('/api/geo/features', [PublicDestinationController::class, 'geoJson'])
    ->middleware('throttle:api')
    ->name('geo.features');

Route::get('/tours', [PublicTourController::class, 'index'])->name('tours.index');
Route::get('/tours/{slug}', [PublicTourController::class, 'show'])->name('tours.show');
Route::get('/tours/{slug}/book', [PublicBookingController::class, 'create'])->name('bookings.create');
Route::post('/tours/{slug}/book', [PublicBookingController::class, 'store'])
    ->middleware('throttle:cms-contact')
    ->name('bookings.store');
Route::get('/bookings/{reference}/confirmation', [PublicBookingController::class, 'confirmation'])->name('bookings.confirmation');
Route::get('/bookings/{reference}/itinerary-offline', [PublicBookingController::class, 'offlineItinerary'])->name('bookings.itinerary-offline');
Route::post('/tours/{slug}/booking-entry', [PublicTourController::class, 'submitBookingEntry'])
    ->middleware('throttle:cms-contact')
    ->name('tours.booking-entry');

// Informational-only hotel detail page linking to eligible tours (NO standalone checkout).
Route::get('/hotels/{slug}', [PublicHotelInfoController::class, 'show'])->name('hotels.show');

Route::get('/fleet', [PublicFleetController::class, 'index'])->name('fleet.index');
Route::get('/digital-nomads', [PublicNomadController::class, 'index'])->name('nomads.index');

Route::get('/verify/invoice/{token}', [InvoicePublicController::class, 'verify'])->name('invoices.verify');
Route::middleware(['auth', 'active'])
    ->get('/invoices/{invoice}/pdf', [InvoicePublicController::class, 'downloadPdf'])
    ->name('invoices.pdf');

Route::post('/cms/contact', [PublicPageController::class, 'submitContact'])
    ->middleware('throttle:cms-contact')
    ->name('cms.contact.submit');

Route::get('/sitemap.xml', [SeoPublicController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoPublicController::class, 'robots'])->name('seo.robots');

Route::middleware(['auth', 'active', 'can:cms.view'])
    ->get('/cms/preview/{page}', [PublicPageController::class, 'preview'])
    ->name('cms.pages.preview');

Route::middleware(['auth', 'active'])
    ->prefix('admin/media')
    ->group(function (): void {
        Route::post('/jodit-upload', [JoditMediaController::class, 'upload'])->name('admin.media.jodit-upload');
        Route::get('/jodit-browse', [JoditMediaController::class, 'browse'])->name('admin.media.jodit-browse');
    });

Route::middleware(['auth', 'active', 'verified', 'customer'])
    ->group(function (): void {
        Route::get('/dashboard', CustomerDashboardController::class)->name('customer.dashboard');
    });

// Published dynamic CMS pages (e.g. /about, /contact, /terms, /privacy, /faqs).
// Explicitly excludes reserved application prefixes so domain, auth, admin, and API routes are never shadowed.
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '^(?!(?:admin|api|dashboard|destinations|regions|tours|bookings|hotels|fleet|digital-nomads|verify|sitemap\.xml|robots\.txt|login|logout|register|forgot-password|reset-password|verify-email|email|cms|livewire|storage|up)(?:/|$))[a-z0-9]+(?:-[a-z0-9]+)*$')
    ->name('cms.pages.show');
