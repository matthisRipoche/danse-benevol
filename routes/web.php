<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/inscription', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/inscription', [RegisteredUserController::class, 'store'])
    ->middleware('throttle:6,1');

Route::get('/inscription/bienvenue', function () {
    return view('auth.registered');
})->middleware('auth')->name('register.confirmation');
