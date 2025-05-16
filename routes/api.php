<?php

use App\Http\Controllers\OAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
Route::get('/oauth/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');

Route::middleware(['salla.auth'])->group(function () {
<<<<<<< Updated upstream
    Route::get('/oauth/refresh-token', [OAuthController::class, 'refreshToken'])->name('oauth.refresh-token');
=======
    // OAuth routes
    Route::post('/oauth/refresh-token', [OAuthController::class, 'refreshToken'])->name('oauth.refresh-token');
>>>>>>> Stashed changes
    Route::get('/oauth/owner', [OAuthController::class, 'getOwnerDetails'])->name('oauth.owner');
    
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
