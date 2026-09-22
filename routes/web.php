<?php

use App\Http\Controllers\Admin\InvitationCodeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PlanningController;
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

Route::get('/connexion', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/connexion', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:6,1');
Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/codes-invitation', [InvitationCodeController::class, 'index'])->name('invitation-codes.index');
    Route::get('/codes-invitation/creer', [InvitationCodeController::class, 'create'])->name('invitation-codes.create');
    Route::post('/codes-invitation', [InvitationCodeController::class, 'store'])->name('invitation-codes.store');
    Route::post('/codes-invitation/{invitationCode}/revoquer', [InvitationCodeController::class, 'revoke'])->name('invitation-codes.revoke');
});

Route::middleware('auth')->group(function () {
    Route::get('/mon-planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::post('/mon-planning/creneaux/{missionSlot}', [PlanningController::class, 'reserve'])->name('planning.reserve');
    Route::delete('/mon-planning/creneaux/{missionSlot}', [PlanningController::class, 'cancel'])->name('planning.cancel');
    Route::post('/mon-planning/valider', [PlanningController::class, 'finalize'])->name('planning.finalize');
});
