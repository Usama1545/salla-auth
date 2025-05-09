<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/salla/redirect', function () {
    return Socialite::driver('salla')->redirect();
})->name('salla.login');

Route::get('/salla/callback', function () {
    $user = Socialite::driver('salla')->user();
    $token = $user->token;
    dd($user);
})->name('salla.callback');
