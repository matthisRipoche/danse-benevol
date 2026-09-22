<?php

use App\Models\Edition;
use App\Models\EventDay;
use App\Models\InvitationCode;
use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an admin sees the right counters for the active edition', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);

    InvitationCode::factory()->for($edition)->create();
    InvitationCode::factory()->used()->for($edition)->create();
    InvitationCode::factory()->revoked()->for($edition)->create();

    $validated = User::factory()->create();
    $validated->editions()->attach($edition->id, ['is_validated' => true, 'validated_at' => now()]);

    $pending = User::factory()->create();
    $pending->editions()->attach($edition->id, ['is_validated' => false]);

    $day = EventDay::factory()->for($edition)->create(['label' => 'Vendredi']);
    $slot1 = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $slot2 = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 2]);

    $missionA = Mission::factory()->for($edition)->create(['name' => 'Accueil exposants']);
    $missionSlotA = MissionSlot::factory()->for($missionA, 'mission')->for($slot1, 'timeSlot')->create(['capacity' => 4]);
    VolunteerAssignment::factory()->count(2)->create(['mission_slot_id' => $missionSlotA->id, 'time_slot_id' => $slot1->id]);

    $missionB = Mission::factory()->restricted()->for($edition)->create(['name' => 'Billetterie']);
    $missionSlotB = MissionSlot::factory()->for($missionB, 'mission')->for($slot2, 'timeSlot')->create(['capacity' => 2]);
    VolunteerAssignment::factory()->create(['mission_slot_id' => $missionSlotB->id, 'time_slot_id' => $slot2->id]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();

    // Codes: 1 pending + 1 used + 1 revoked = 3 total.
    $response->assertSee('3');
    $response->assertSee('1 en attente');
    $response->assertSee('1 utilisés');
    $response->assertSee('1 révoqués');

    // Volunteers: 2 registered, 1 validated, 1 pending.
    $response->assertSee('2', false);
    $response->assertSee('1/2');
    $response->assertSee('1 en attente');

    // Fill rate: 3 filled out of 6 capacity = 50% overall, per day, per mission.
    $response->assertSeeInOrder(['Vendredi', '3/6', '50%']);
    $response->assertSeeInOrder(['Accueil exposants', '2/4', '50%']);
    $response->assertSeeInOrder(['Billetterie', '1/2', '50%']);
});

test('a volunteer cannot access the admin dashboard', function () {
    $volunteer = User::factory()->create();

    $response = $this->actingAs($volunteer)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

test('a guest is redirected to the login page', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});
