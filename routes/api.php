<?php

use App\Http\Controllers\OAuthController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::Post('login', [OAuthController::class, 'login'])->name('login');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/oauth/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
    Route::post('/oauth/refresh-token', [OAuthController::class, 'refreshToken'])->name('oauth.refresh-token');
});
Route::post('/webhook', WebhookController::class)->name('webhook');