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

function registeredVolunteer(Edition $edition, array $userAttributes = []): User
{
    $volunteer = User::factory()->create($userAttributes);
    $volunteer->editions()->attach($edition->id);

    return $volunteer;
}

test('an admin sees restricted missions with their assigned volunteers, never public missions', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);

    $restricted = Mission::factory()->restricted()->for($edition)->create(['name' => 'Billetterie']);
    $restrictedSlot = MissionSlot::factory()->for($restricted, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 2]);

    $public = Mission::factory()->for($edition)->create(['name' => 'Accueil exposants']);
    MissionSlot::factory()->for($public, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 5]);

    $volunteer = registeredVolunteer($edition, ['first_name' => 'Camille', 'last_name' => 'Martin']);
    VolunteerAssignment::factory()->validated()->create([
        'user_id' => $volunteer->id,
        'mission_slot_id' => $restrictedSlot->id,
        'time_slot_id' => $timeSlot->id,
        'assigned_by_id' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.restricted-missions.index'));

    $response->assertOk();
    $response->assertSee('Billetterie');
    $response->assertSee('Camille Martin');
    $response->assertDontSee('Accueil exposants');
});

test('an admin can assign a registered volunteer to an available restricted slot', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->restricted()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 2]);

    $volunteer = registeredVolunteer($edition, ['email' => 'volontaire@example.com']);

    $response = $this->actingAs($admin)->post(route('admin.restricted-missions.assign', $missionSlot), [
        'email' => 'volontaire@example.com',
    ]);

    $response->assertRedirect();

    $assignment = VolunteerAssignment::where('user_id', $volunteer->id)->where('mission_slot_id', $missionSlot->id)->firstOrFail();
    expect($assignment->status)->toBe('validated')
        ->and($assignment->assigned_by_id)->toBe($admin->id);

    expect(AuditLog::where('action', 'volunteer_assignment.forced')
        ->where('subject_id', $assignment->id)
        ->exists())->toBeTrue();
});

test('a forced assignment ignores the volunteer\'s own slot quota', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active', 'max_slots_per_volunteer' => 1]);
    $day = EventDay::factory()->for($edition)->create();

    $ownTimeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $publicMission = Mission::factory()->for($edition)->create();
    $ownMissionSlot = MissionSlot::factory()->for($publicMission, 'mission')->for($ownTimeSlot, 'timeSlot')->create(['capacity' => 3]);

    $restrictedTimeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 2]);
    $restrictedMission = Mission::factory()->restricted()->for($edition)->create();
    $restrictedSlot = MissionSlot::factory()->for($restrictedMission, 'mission')->for($restrictedTimeSlot, 'timeSlot')->create(['capacity' => 2]);

    $volunteer = registeredVolunteer($edition, ['email' => 'complet@example.com']);
    VolunteerAssignment::factory()->create([
        'user_id' => $volunteer->id,
        'mission_slot_id' => $ownMissionSlot->id,
        'time_slot_id' => $ownTimeSlot->id,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.restricted-missions.assign', $restrictedSlot), [
        'email' => 'complet@example.com',
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect(VolunteerAssignment::where('user_id', $volunteer->id)->count())->toBe(2);
});

test('assignment fails when the email does not match any account', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->restricted()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 2]);

    $response = $this->actingAs($admin)->post(route('admin.restricted-missions.assign', $missionSlot), [
        'email' => 'inconnu@example.com',
    ]);

    $response->assertSessionHasErrors('email');
    expect(VolunteerAssignment::count())->toBe(0);
});

test('assignment fails when the volunteer is not registered for the active edition', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->restricted()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 2]);

    User::factory()->create(['email' => 'non-inscrit@example.com']);

    $response = $this->actingAs($admin)->post(route('admin.restricted-missions.assign', $missionSlot), [
        'email' => 'non-inscrit@example.com',
    ]);

    $response->assertSessionHasErrors('email');
    expect(VolunteerAssignment::count())->toBe(0);
});

test('assignment fails when the restricted slot is full', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->restricted()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 1]);

    VolunteerAssignment::factory()->create(['mission_slot_id' => $missionSlot->id, 'time_slot_id' => $timeSlot->id]);

    $volunteer = registeredVolunteer($edition, ['email' => 'volontaire@example.com']);

    $response = $this->actingAs($admin)->post(route('admin.restricted-missions.assign', $missionSlot), [
        'email' => 'volontaire@example.com',
    ]);

    $response->assertSessionHas('error');
    expect(VolunteerAssignment::where('user_id', $volunteer->id)->count())->toBe(0);
});

test('assignment fails when the volunteer already has a mission on that time slot', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);

    $publicMission = Mission::factory()->for($edition)->create();
    $publicSlot = MissionSlot::factory()->for($publicMission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);

    $restrictedMission = Mission::factory()->restricted()->for($edition)->create();
    $restrictedSlot = MissionSlot::factory()->for($restrictedMission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 2]);

    $volunteer = registeredVolunteer($edition, ['email' => 'occupe@example.com']);
    VolunteerAssignment::factory()->create([
        'user_id' => $volunteer->id,
        'mission_slot_id' => $publicSlot->id,
        'time_slot_id' => $timeSlot->id,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.restricted-missions.assign', $restrictedSlot), [
        'email' => 'occupe@example.com',
    ]);

    $response->assertSessionHas('error');
    expect(VolunteerAssignment::where('user_id', $volunteer->id)->count())->toBe(1);
});

test('an admin can remove an existing restricted assignment', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->restricted()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 2]);

    $volunteer = registeredVolunteer($edition);
    $assignment = VolunteerAssignment::factory()->validated()->create([
        'user_id' => $volunteer->id,
        'mission_slot_id' => $missionSlot->id,
        'time_slot_id' => $timeSlot->id,
        'assigned_by_id' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->delete(route('admin.restricted-missions.unassign', $assignment));

    $response->assertRedirect();
    expect(VolunteerAssignment::find($assignment->id))->toBeNull();
    expect(AuditLog::where('action', 'volunteer_assignment.unassigned')->exists())->toBeTrue();
});

test('a volunteer cannot access the restricted missions admin area', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = registeredVolunteer($edition);

    $response = $this->actingAs($volunteer)->get(route('admin.restricted-missions.index'));

    $response->assertForbidden();
});

test('a guest is redirected to the login page', function () {
    $response = $this->get(route('admin.restricted-missions.index'));

    $response->assertRedirect(route('login'));
});
