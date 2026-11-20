<?php

use App\Modules\Cms\Http\Controllers\PublicPageController;
use App\Modules\Identity\Http\Controllers\CustomerDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');

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
// Explicitly excludes reserved application prefixes so auth, admin, and API routes are never shadowed.
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '^(?!(?:admin|api|dashboard|login|logout|register|forgot-password|reset-password|verify-email|email|cms|livewire|storage|up)(?:/|$))[a-z0-9]+(?:-[a-z0-9]+)*$')
    ->name('cms.pages.show');
