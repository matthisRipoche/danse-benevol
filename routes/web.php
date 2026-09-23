<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvitationCodeController;
use App\Http\Controllers\Admin\RestrictedMissionController;
use App\Http\Controllers\Admin\VolunteerController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->homeRouteName())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/inscription', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::get('/connexion', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/connexion', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1');

    // Raccourci de connexion admin réservé au développement local — jamais actif en dehors de `local`.
    Route::post('/connexion/dev-admin', function () {
        abort_unless(app()->environment('local'), 404);

        $admin = User::where('role', 'admin')->first();

        abort_unless($admin, 404, 'Aucun compte admin en base — lance le seeder (php artisan db:seed).');

        Auth::login($admin);

        return redirect()->route($admin->homeRouteName());
    })->name('dev-login.admin');
});
Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/benevoles', [VolunteerController::class, 'index'])->name('volunteers.index');
    Route::get('/benevoles/{user}', [VolunteerController::class, 'show'])->name('volunteers.show');
    Route::get('/benevoles/{user}/photo', [VolunteerController::class, 'photo'])->name('volunteers.photo');
    Route::post('/benevoles/{user}/valider-mineur', [VolunteerController::class, 'validateMinor'])->name('volunteers.validate-minor');

    Route::get('/codes-invitation', [InvitationCodeController::class, 'index'])->name('invitation-codes.index');
    Route::get('/codes-invitation/creer', [InvitationCodeController::class, 'create'])->name('invitation-codes.create');
    Route::post('/codes-invitation', [InvitationCodeController::class, 'store'])->name('invitation-codes.store');
    Route::post('/codes-invitation/{invitationCode}/revoquer', [InvitationCodeController::class, 'revoke'])->name('invitation-codes.revoke');

    Route::get('/postes-restreints', [RestrictedMissionController::class, 'index'])->name('restricted-missions.index');
    Route::post('/postes-restreints/{missionSlot}/assigner', [RestrictedMissionController::class, 'assign'])->name('restricted-missions.assign');
    Route::delete('/postes-restreints/{volunteerAssignment}', [RestrictedMissionController::class, 'unassign'])->name('restricted-missions.unassign');
});

Route::middleware('auth')->group(function () {
    Route::get('/mon-planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::post('/mon-planning/creneaux/{missionSlot}', [PlanningController::class, 'reserve'])->name('planning.reserve');
    Route::delete('/mon-planning/creneaux/{missionSlot}', [PlanningController::class, 'cancel'])->name('planning.cancel');
    Route::post('/mon-planning/valider', [PlanningController::class, 'finalize'])->name('planning.finalize');

    Route::get('/mon-profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/mon-profil/photo', [ProfileController::class, 'photo'])->name('profile.photo');
});
