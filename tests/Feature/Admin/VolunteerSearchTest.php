<?php

use App\Models\Edition;
use App\Models\EventDay;
use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function attachVolunteer(Edition $edition, array $userAttributes = [], array $pivot = []): User
{
    $volunteer = User::factory()->create($userAttributes);
    $volunteer->editions()->attach($edition->id, $pivot);

    return $volunteer;
}

test('an admin can search volunteers by last name', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    attachVolunteer($edition, ['first_name' => 'Camille', 'last_name' => 'Dupont']);
    attachVolunteer($edition, ['first_name' => 'Alex', 'last_name' => 'Martin']);

    $response = $this->actingAs($admin)->get(route('admin.volunteers.index', ['nom' => 'Dupont']));

    $response->assertOk();
    $response->assertSee('Dupont');
    $response->assertDontSee('Martin');
});

test('an admin can search volunteers by first name', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    attachVolunteer($edition, ['first_name' => 'Camille', 'last_name' => 'Dupont']);
    attachVolunteer($edition, ['first_name' => 'Alex', 'last_name' => 'Martin']);

    $response = $this->actingAs($admin)->get(route('admin.volunteers.index', ['prenom' => 'Camille']));

    $response->assertOk();
    $response->assertSee('Dupont');
    $response->assertDontSee('Martin');
});

test('an admin can filter volunteers by mission', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $slot1 = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $slot2 = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 2]);

    $missionA = Mission::factory()->for($edition)->create(['name' => 'Accueil exposants']);
    $missionSlotA = MissionSlot::factory()->for($missionA, 'mission')->for($slot1, 'timeSlot')->create();

    $missionB = Mission::factory()->for($edition)->create(['name' => 'Vestiaires']);
    $missionSlotB = MissionSlot::factory()->for($missionB, 'mission')->for($slot2, 'timeSlot')->create();

    $volunteerA = attachVolunteer($edition, ['first_name' => 'Camille', 'last_name' => 'Dupont']);
    VolunteerAssignment::factory()->create(['user_id' => $volunteerA->id, 'mission_slot_id' => $missionSlotA->id, 'time_slot_id' => $slot1->id]);

    $volunteerB = attachVolunteer($edition, ['first_name' => 'Alex', 'last_name' => 'Martin']);
    VolunteerAssignment::factory()->create(['user_id' => $volunteerB->id, 'mission_slot_id' => $missionSlotB->id, 'time_slot_id' => $slot2->id]);

    $response = $this->actingAs($admin)->get(route('admin.volunteers.index', ['mission' => $missionA->id]));

    $response->assertOk();
    $response->assertSee('Dupont');
    $response->assertDontSee('Martin');
});

test('an admin can filter volunteers by day', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $dayA = EventDay::factory()->for($edition)->create(['date' => '2027-05-14', 'label' => 'Vendredi']);
    $dayB = EventDay::factory()->for($edition)->create(['date' => '2027-05-15', 'label' => 'Samedi']);
    $slotA = TimeSlot::factory()->for($dayA, 'eventDay')->create(['position' => 1]);
    $slotB = TimeSlot::factory()->for($dayB, 'eventDay')->create(['position' => 1]);

    $mission = Mission::factory()->for($edition)->create();
    $missionSlotA = MissionSlot::factory()->for($mission, 'mission')->for($slotA, 'timeSlot')->create();
    $missionSlotB = MissionSlot::factory()->for($mission, 'mission')->for($slotB, 'timeSlot')->create();

    $volunteerA = attachVolunteer($edition, ['first_name' => 'Camille', 'last_name' => 'Dupont']);
    VolunteerAssignment::factory()->create(['user_id' => $volunteerA->id, 'mission_slot_id' => $missionSlotA->id, 'time_slot_id' => $slotA->id]);

    $volunteerB = attachVolunteer($edition, ['first_name' => 'Alex', 'last_name' => 'Martin']);
    VolunteerAssignment::factory()->create(['user_id' => $volunteerB->id, 'mission_slot_id' => $missionSlotB->id, 'time_slot_id' => $slotB->id]);

    $response = $this->actingAs($admin)->get(route('admin.volunteers.index', ['jour' => $dayA->id]));

    $response->assertOk();
    $response->assertSee('Dupont');
    $response->assertDontSee('Martin');
});

test('an admin can filter volunteers by planning status', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    attachVolunteer($edition, ['first_name' => 'Camille', 'last_name' => 'Dupont'], ['is_validated' => true]);
    attachVolunteer($edition, ['first_name' => 'Alex', 'last_name' => 'Martin'], ['is_validated' => false]);

    $validated = $this->actingAs($admin)->get(route('admin.volunteers.index', ['statut' => 'valide']));
    $validated->assertSee('Dupont');
    $validated->assertDontSee('Martin');

    $pending = $this->actingAs($admin)->get(route('admin.volunteers.index', ['statut' => 'attente']));
    $pending->assertSee('Martin');
    $pending->assertDontSee('Dupont');
});

test('combining filters narrows the results further', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    attachVolunteer($edition, ['first_name' => 'Camille', 'last_name' => 'Dupont'], ['is_validated' => true]);
    attachVolunteer($edition, ['first_name' => 'Sacha', 'last_name' => 'Dupont'], ['is_validated' => false]);

    $response = $this->actingAs($admin)->get(route('admin.volunteers.index', ['nom' => 'Dupont', 'statut' => 'valide']));

    $response->assertOk();
    $response->assertSee('Camille');
    $response->assertDontSee('Sacha');
});

test('a volunteer cannot access the admin volunteer search', function () {
    $volunteer = User::factory()->create();

    $response = $this->actingAs($volunteer)->get(route('admin.volunteers.index'));

    $response->assertForbidden();
});

test('a guest is redirected to the login page', function () {
    $response = $this->get(route('admin.volunteers.index'));

    $response->assertRedirect(route('login'));
});
