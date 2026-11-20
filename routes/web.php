<?php

use App\Modules\Cms\Http\Controllers\PublicHomeController;
use App\Modules\Identity\Http\Controllers\CustomerDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicHomeController::class)->name('home');

Route::middleware(['auth', 'active', 'verified', 'customer'])
    ->group(function (): void {
        Route::get('/dashboard', CustomerDashboardController::class)->name('customer.dashboard');
    });
