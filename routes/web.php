<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\UnsplashController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\SendCampaignController;
use App\Http\Controllers\NewsletterListController;
use App\Http\Controllers\BulkDeleteListsController;
use App\Http\Controllers\PublicNewsletterController;
use App\Http\Controllers\Email\TrackEmailOpenController;
use App\Http\Controllers\NewsletterSubscriberController;
use App\Http\Controllers\Email\TrackEmailClickController;
use App\Http\Controllers\Email\UnsubscribeEmailController;
use App\Http\Controllers\ToggleSubscriberStatusController;
use App\Http\Controllers\NewsletterSubscriberImportController;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::delete('lists/bulk-delete', BulkDeleteListsController::class)->name('lists.bulk-delete');
    Route::resource('lists', NewsletterListController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy'])
        ->names('lists');
    Route::resource('lists.subscribers', NewsletterSubscriberController::class)
        ->only(['store', 'destroy'])
        ->names('subscribers');
    Route::post('subscribers/{subscriber}/toggle-status', ToggleSubscriberStatusController::class)->name('subscribers.toggle-status');
    Route::resource('campaigns', CampaignController::class);
    Route::get('campaigns/{campaign}/content', [CampaignController::class, 'content'])->name('campaigns.content');
    Route::put('campaigns/{campaign}/content', [CampaignController::class, 'updateContent'])->name('campaigns.content.update');
    Route::post('campaigns/{campaign}/send', SendCampaignController::class)->name('campaigns.send');
    Route::get('images', [ImageUploadController::class, 'index'])->name('images.index');
    Route::post('images/upload', [ImageUploadController::class, 'store'])->name('images.upload');
    Route::post('campaigns/{campaign}/images/upload', [ImageUploadController::class, 'store'])->name('campaigns.images.upload');
    Route::delete('images', [ImageUploadController::class, 'destroy'])->name('images.destroy');
    Route::post('imports', [NewsletterSubscriberImportController::class, 'store'])->name('imports.store');

    // Unsplash integration routes
    Route::get('unsplash/search', [UnsplashController::class, 'search'])->name('unsplash.search');
    Route::post('unsplash/download', [UnsplashController::class, 'download'])->name('unsplash.download');
    Route::post('campaigns/{campaign}/unsplash/download', [UnsplashController::class, 'download'])->name('campaigns.unsplash.download');
});

// Email tracking routes (no middleware required)
Route::get('track/open/{campaign}/{subscriber}', TrackEmailOpenController::class)->name('campaign.track.open');
Route::get('track/click/{campaign}/{subscriber}', TrackEmailClickController::class)->name('campaign.track.click');
Route::get('unsubscribe', UnsubscribeEmailController::class)->name('newsletter.unsubscribe');

// Public newsletter signup routes
Route::get('newsletter/{shortcode}', [PublicNewsletterController::class, 'show'])->name('newsletter.signup');
Route::post('newsletter/{shortcode}/subscribe', [PublicNewsletterController::class, 'subscribe'])->name('newsletter.subscribe');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
