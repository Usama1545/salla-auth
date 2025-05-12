<?php

use App\Http\Controllers\OAuthController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;


// Route::group(['middleware' => 'auth'], function () {
    Route::get('/oauth/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
// });
Route::post('/webhook', WebhookController::class)->name('webhook');
