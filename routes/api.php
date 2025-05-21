<?php

use App\Http\Controllers\OAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SocialLinkController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
Route::get('/oauth/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');

Route::middleware(['salla.auth'])->group(function () {
    Route::get('/oauth/refresh-token', [OAuthController::class, 'refreshToken'])->name('oauth.refresh-token');
    Route::get('/oauth/owner', [OAuthController::class, 'getOwnerDetails'])->name('oauth.owner');
    
    // Social Links routes
    Route::apiResource('social-links', SocialLinkController::class);
    Route::get('social-tracking-settings', [SocialLinkController::class, 'settings'])->name('social-tracking-settings');
    Route::get('/store/{storeId}/social-links', [SocialLinkController::class, 'getByStoreId'])->name('social-links.by-store');
    
    // Facebook Conversion API route
    Route::post('/facebook/conversion', [SocialLinkController::class, 'trackFacebookEvent'])->name('facebook.conversion');
    
    // Product API routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::get('/{id}', [ProductController::class, 'show'])->name('products.show');
        Route::post('/', [ProductController::class, 'store'])->name('products.store');
        Route::put('/{id}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::put('/{id}/status', [ProductController::class, 'updateStatus'])->name('products.status');
    });
});

Route::post('/webhook', WebhookController::class)->name('webhook');
