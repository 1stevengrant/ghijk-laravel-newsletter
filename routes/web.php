<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NewsletterSubscriberController;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('subscribers', NewsletterSubscriberController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names('subscribers');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
