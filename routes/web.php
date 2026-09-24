<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\InvitationCodeController;
use App\Http\Controllers\Admin\MissionController;
use App\Http\Controllers\Admin\RegistrationWindowController;
use App\Http\Controllers\Admin\RestrictedMissionController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\VolunteerController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\JoinEditionController;
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

    Route::get('/mot-de-passe-oublie', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/reinitialiser-mot-de-passe/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');

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
    Route::put('/inscriptions', [RegistrationWindowController::class, 'update'])->name('registration-window.update');
    Route::post('/inscriptions/suspendre', [RegistrationWindowController::class, 'toggleLock'])->name('registration-window.toggle-lock');

    Route::get('/benevoles', [VolunteerController::class, 'index'])->name('volunteers.index');
    Route::get('/benevoles/{user}', [VolunteerController::class, 'show'])->name('volunteers.show');
    Route::get('/benevoles/{user}/modifier', [VolunteerController::class, 'edit'])->name('volunteers.edit');
    Route::put('/benevoles/{user}', [VolunteerController::class, 'update'])->name('volunteers.update');
    Route::get('/benevoles/{user}/photo', [VolunteerController::class, 'photo'])->name('volunteers.photo');
    Route::get('/badges/{badgeUid}', [VolunteerController::class, 'badge'])->name('volunteers.badge');
    Route::post('/benevoles/{user}/valider-mineur', [VolunteerController::class, 'validateMinor'])->name('volunteers.validate-minor');

    Route::get('/codes-invitation', [InvitationCodeController::class, 'index'])->name('invitation-codes.index');
    Route::get('/codes-invitation/creer', [InvitationCodeController::class, 'create'])->name('invitation-codes.create');
    Route::get('/codes-invitation/importer', [InvitationCodeController::class, 'importForm'])->name('invitation-codes.import');
    Route::post('/codes-invitation/importer', [InvitationCodeController::class, 'import'])->name('invitation-codes.import.store');
    Route::post('/codes-invitation', [InvitationCodeController::class, 'store'])->name('invitation-codes.store');
    Route::post('/codes-invitation/{invitationCode}/revoquer', [InvitationCodeController::class, 'revoke'])->name('invitation-codes.revoke');

    Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');
    Route::get('/exports/{type}/{format}', [ExportController::class, 'download'])->name('exports.download');

    Route::get('/missions', [MissionController::class, 'index'])->name('missions.index');
    Route::get('/missions/creer', [MissionController::class, 'create'])->name('missions.create');
    Route::post('/missions', [MissionController::class, 'store'])->name('missions.store');
    Route::get('/missions/importer', [MissionController::class, 'importForm'])->name('missions.import');
    Route::post('/missions/importer', [MissionController::class, 'import'])->name('missions.import.store');
    Route::get('/missions/{mission}/modifier', [MissionController::class, 'edit'])->name('missions.edit');
    Route::put('/missions/{mission}', [MissionController::class, 'update'])->name('missions.update');
    Route::delete('/missions/{mission}', [MissionController::class, 'destroy'])->name('missions.destroy');

    Route::get('/jours-et-creneaux', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/jours', [ScheduleController::class, 'storeDay'])->name('schedule.days.store');
    Route::delete('/jours/{eventDay}', [ScheduleController::class, 'destroyDay'])->name('schedule.days.destroy');
    Route::post('/jours/{eventDay}/creneaux', [ScheduleController::class, 'storeTimeSlot'])->name('schedule.time-slots.store');
    Route::delete('/creneaux/{timeSlot}', [ScheduleController::class, 'destroyTimeSlot'])->name('schedule.time-slots.destroy');

    Route::get('/postes-restreints', [RestrictedMissionController::class, 'index'])->name('restricted-missions.index');
    Route::post('/postes-restreints/{missionSlot}/assigner', [RestrictedMissionController::class, 'assign'])->name('restricted-missions.assign');
    Route::delete('/postes-restreints/{volunteerAssignment}', [RestrictedMissionController::class, 'unassign'])->name('restricted-missions.unassign');
});

Route::middleware('auth')->group(function () {
    Route::get('/rejoindre', [JoinEditionController::class, 'create'])->name('edition.join');
    Route::post('/rejoindre', [JoinEditionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('edition.join.store');

    Route::get('/mon-planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::post('/mon-planning/creneaux/{missionSlot}', [PlanningController::class, 'reserve'])->name('planning.reserve');
    Route::delete('/mon-planning/creneaux/{missionSlot}', [PlanningController::class, 'cancel'])->name('planning.cancel');
    Route::post('/mon-planning/valider', [PlanningController::class, 'finalize'])->name('planning.finalize');

    Route::get('/mon-profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/mon-profil/modifier', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mon-profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/mon-profil/photo', [ProfileController::class, 'photo'])->name('profile.photo');
});
