<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTrackingController;
use App\Http\Controllers\NewsletterListController;
use App\Http\Controllers\NewsletterSubscriberController;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('lists', NewsletterListController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy'])
        ->names('lists');
    Route::resource('lists.subscribers', NewsletterSubscriberController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names('subscribers');
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
});

// Email tracking routes (no middleware required)
Route::get('track/open/{campaign}/{subscriber}', [EmailTrackingController::class, 'trackOpen'])->name('campaign.track.open');
Route::get('track/click/{campaign}/{subscriber}', [EmailTrackingController::class, 'trackClick'])->name('campaign.track.click');
Route::get('unsubscribe', [EmailTrackingController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
