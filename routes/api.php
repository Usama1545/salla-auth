<?php

use App\Http\Controllers\OAuthController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
Route::get('/oauth/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');

Route::middleware(['salla.auth'])->group(function () {
    Route::post('/oauth/refresh-token', [OAuthController::class, 'refreshToken'])->name('oauth.refresh-token');
    Route::get('/oauth/owner', [OAuthController::class, 'getOwnerDetails'])->name('oauth.owner');
});

Route::post('/webhook', WebhookController::class)->name('webhook');