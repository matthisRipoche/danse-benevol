<?php

use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\EventDay;
use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: MissionSlot}
 */
function volunteerWithSlot(Edition $edition): array
{
    $timeSlot = TimeSlot::factory()->for(EventDay::factory()->for($edition), 'eventDay')->create(['position' => 1]);
    $missionSlot = MissionSlot::factory()->for(Mission::factory()->for($edition), 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);
    $volunteer = User::factory()->create();
    $volunteer->editions()->attach($edition->id);

    return [$volunteer, $missionSlot];
}

test('the registration status follows the dates and the manual lock', function (array $attributes, string $expected) {
    $edition = Edition::factory()->make($attributes);

    expect($edition->registrationStatus())->toBe($expected)
        ->and($edition->isRegistrationOpen())->toBe($expected === 'open');
})->with([
    'dans la fenêtre' => [['registration_opens_at' => now()->subDay(), 'registration_closes_at' => now()->addDay()], 'open'],
    'sans dates' => [['registration_opens_at' => null, 'registration_closes_at' => null], 'open'],
    'avant l\'ouverture' => [['registration_opens_at' => now()->addDay(), 'registration_closes_at' => now()->addWeek()], 'not_open'],
    'après la fermeture' => [['registration_opens_at' => now()->subWeek(), 'registration_closes_at' => now()->subMinute()], 'closed'],
    'suspendue' => [['registration_opens_at' => now()->subDay(), 'registration_closes_at' => now()->addDay(), 'is_registration_locked' => true], 'locked'],
]);

test('booking, cancelling and validating are refused outside the registration window', function (string $state, string $messagePart) {
    $edition = Edition::factory()->{$state}()->create(['status' => 'active']);
    [$volunteer, $missionSlot] = volunteerWithSlot($edition);
    $bookedSlot = MissionSlot::factory()
        ->for(Mission::factory()->for($edition), 'mission')
        ->for(TimeSlot::factory()->for(EventDay::factory()->for($edition), 'eventDay')->create(['position' => 2]), 'timeSlot')
        ->create();
    VolunteerAssignment::factory()->for($volunteer)->for($bookedSlot)->create(['time_slot_id' => $bookedSlot->time_slot_id, 'status' => 'draft']);

    $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlot))
        ->assertSessionHas('error', fn (string $error) => str_contains($error, $messagePart));
    $this->actingAs($volunteer)->delete(route('planning.cancel', $bookedSlot))
        ->assertSessionHas('error', fn (string $error) => str_contains($error, $messagePart));
    $this->actingAs($volunteer)->post(route('planning.finalize'))
        ->assertSessionHas('error', fn (string $error) => str_contains($error, $messagePart));

    expect(VolunteerAssignment::where('user_id', $volunteer->id)->pluck('mission_slot_id')->all())->toBe([$bookedSlot->id])
        ->and($volunteer->editions()->first()->pivot->is_validated)->toBeFalse();
})->with([
    'pas encore ouvertes' => ['registrationNotOpen', 'Les inscriptions ouvriront le'],
    'closes' => ['registrationClosed', 'Les inscriptions sont closes depuis le'],
    'suspendues' => ['registrationLocked', 'momentanément suspendues'],
]);

test('booking works inside the registration window', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    [$volunteer, $missionSlot] = volunteerWithSlot($edition);

    $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlot))->assertSessionHas('status', 'Créneau réservé.');
});

test('the planning is read-only outside the window, with the opening date in Paris time', function () {
    $edition = Edition::factory()->create([
        'status' => 'active',
        'registration_opens_at' => '2099-03-01 08:00:00',
        'registration_closes_at' => '2099-04-01 20:00:00',
    ]);
    [$volunteer, $missionSlot] = volunteerWithSlot($edition);

    $this->actingAs($volunteer)->get(route('planning.index'))
        ->assertOk()
        ->assertSee('Lecture seule')
        ->assertSee('Les inscriptions ouvriront le 01/03/2099 à 09:00')
        ->assertDontSee(route('planning.reserve', $missionSlot))
        ->assertDontSee('finalize-dialog');
});

test('admin actions on restricted posts are not affected by the window', function () {
    $edition = Edition::factory()->registrationClosed()->create(['status' => 'active']);
    $timeSlot = TimeSlot::factory()->for(EventDay::factory()->for($edition), 'eventDay')->create(['position' => 1]);
    $restrictedSlot = MissionSlot::factory()->for(Mission::factory()->restricted()->for($edition), 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 2]);
    User::factory()->create(['email' => 'caisse@example.fr'])->editions()->attach($edition->id);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.restricted-missions.assign', $restrictedSlot), ['email' => 'caisse@example.fr'])
        ->assertSessionHas('status');
});

test('an admin sets the window in Paris time, stored in UTC', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);

    $this->actingAs($admin)->put(route('admin.registration-window.update'), [
        'registration_opens_at' => '2027-03-01T09:00',
        'registration_closes_at' => '2027-04-30T23:59',
    ])->assertRedirect(route('admin.dashboard'));

    $edition->refresh();
    expect($edition->registration_opens_at->utc()->format('Y-m-d H:i'))->toBe('2027-03-01 08:00')
        ->and($edition->registration_closes_at->utc()->format('Y-m-d H:i'))->toBe('2027-04-30 21:59')
        ->and(AuditLog::where('action', 'edition.registration_window_updated')->exists())->toBeTrue();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertSee('value="2027-03-01T09:00"', false)
        ->assertSee('value="2027-04-30T23:59"', false);
});

test('empty dates leave the window open', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->registrationClosed()->create(['status' => 'active']);

    $this->actingAs($admin)->put(route('admin.registration-window.update'), [
        'registration_opens_at' => '',
        'registration_closes_at' => '',
    ]);

    expect($edition->fresh()->registrationStatus())->toBe('open');
});

test('the closing date must come after the opening date', function () {
    $admin = User::factory()->admin()->create();
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs($admin)->put(route('admin.registration-window.update'), [
        'registration_opens_at' => '2027-04-01T09:00',
        'registration_closes_at' => '2027-03-01T09:00',
    ])->assertSessionHasErrors('registration_closes_at');
});

test('an admin can suspend then reopen registrations', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);

    $this->actingAs($admin)->post(route('admin.registration-window.toggle-lock'))->assertRedirect(route('admin.dashboard'));
    expect($edition->fresh()->registrationStatus())->toBe('locked');
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('Suspendues')->assertSee('Rouvrir les inscriptions');

    $this->actingAs($admin)->post(route('admin.registration-window.toggle-lock'));
    expect($edition->fresh()->registrationStatus())->toBe('open')
        ->and(AuditLog::whereIn('action', ['edition.registration_locked', 'edition.registration_unlocked'])->count())->toBe(2);
});

test('volunteers cannot change the registration window', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = User::factory()->create();

    $this->actingAs($volunteer)->post(route('admin.registration-window.toggle-lock'))->assertForbidden();
    $this->actingAs($volunteer)->put(route('admin.registration-window.update'), ['registration_opens_at' => ''])->assertForbidden();

    expect($edition->fresh()->is_registration_locked)->toBeFalse();
});
