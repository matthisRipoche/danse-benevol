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

function volunteerFor(Edition $edition, array $pivot = []): User
{
    $user = User::factory()->create();
    $user->editions()->attach($edition->id, $pivot);

    return $user;
}

test('a registered volunteer sees only public missions with their gauge', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);

    $publicMission = Mission::factory()->for($edition)->create(['name' => 'Accueil exposants']);
    MissionSlot::factory()->for($publicMission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 5]);

    $restrictedMission = Mission::factory()->restricted()->for($edition)->create(['name' => 'Billetterie']);
    MissionSlot::factory()->for($restrictedMission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 2]);

    $volunteer = volunteerFor($edition);

    $response = $this->actingAs($volunteer)->get(route('planning.index'));

    $response->assertOk();
    $response->assertSee('Accueil exposants');
    $response->assertDontSee('Billetterie');
    $response->assertSee('Déconnexion');
});

test('a volunteer not registered for the active edition is sent to the join page', function () {
    Edition::factory()->create(['status' => 'active']);
    $volunteer = User::factory()->create();

    $response = $this->actingAs($volunteer)->get(route('planning.index'));

    $response->assertRedirect(route('edition.join'));
});

test('a volunteer not registered for the active edition still cannot book a slot', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $timeSlot = TimeSlot::factory()->for(EventDay::factory()->for($edition), 'eventDay')->create(['position' => 1]);
    $missionSlot = MissionSlot::factory()->for(Mission::factory()->for($edition), 'mission')->for($timeSlot, 'timeSlot')->create();
    $volunteer = User::factory()->create();

    $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlot))->assertForbidden();
});

test('a volunteer can reserve an available slot as a draft', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);

    $volunteer = volunteerFor($edition);

    $response = $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlot));

    $response->assertRedirect();
    expect(VolunteerAssignment::where('user_id', $volunteer->id)
        ->where('mission_slot_id', $missionSlot->id)
        ->where('status', 'draft')
        ->exists())->toBeTrue();
});

test('reservation fails when the mission slot is full', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 1]);

    VolunteerAssignment::factory()->create(['mission_slot_id' => $missionSlot->id]);

    $volunteer = volunteerFor($edition);

    $response = $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlot));

    $response->assertSessionHas('error');
    expect(VolunteerAssignment::where('user_id', $volunteer->id)->count())->toBe(0);
});

test('reservation fails when the volunteer already has a mission on that time slot', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);

    $missionA = Mission::factory()->for($edition)->create();
    $missionSlotA = MissionSlot::factory()->for($missionA, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);

    $missionB = Mission::factory()->for($edition)->create();
    $missionSlotB = MissionSlot::factory()->for($missionB, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);

    $volunteer = volunteerFor($edition);
    $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlotA));

    $response = $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlotB));

    $response->assertSessionHas('error');
    expect(VolunteerAssignment::where('user_id', $volunteer->id)->count())->toBe(1);
});

test('reservation fails beyond the edition\'s max slots per volunteer', function () {
    $edition = Edition::factory()->create(['status' => 'active', 'max_slots_per_volunteer' => 2]);
    $day = EventDay::factory()->for($edition)->create();
    $mission = Mission::factory()->for($edition)->create();

    $slots = collect([1, 3, 5])->map(function (int $position) use ($day, $mission) {
        $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => $position]);

        return MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);
    });

    $volunteer = volunteerFor($edition);
    $this->actingAs($volunteer)->post(route('planning.reserve', $slots[0]));
    $this->actingAs($volunteer)->post(route('planning.reserve', $slots[1]));

    $response = $this->actingAs($volunteer)->post(route('planning.reserve', $slots[2]));

    $response->assertSessionHas('error');
    expect(VolunteerAssignment::where('user_id', $volunteer->id)->count())->toBe(2);
});

test('reservation fails when it would exceed max consecutive slots', function () {
    $edition = Edition::factory()->create(['status' => 'active', 'max_slots_per_volunteer' => 3, 'max_consecutive_slots' => 2]);
    $day = EventDay::factory()->for($edition)->create();
    $mission = Mission::factory()->for($edition)->create();

    $slots = collect([1, 2, 3])->map(function (int $position) use ($day, $mission) {
        $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => $position]);

        return MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);
    });

    $volunteer = volunteerFor($edition);
    $this->actingAs($volunteer)->post(route('planning.reserve', $slots[0]));
    $this->actingAs($volunteer)->post(route('planning.reserve', $slots[1]));

    $response = $this->actingAs($volunteer)->post(route('planning.reserve', $slots[2]));

    $response->assertSessionHas('error');
    expect(VolunteerAssignment::where('user_id', $volunteer->id)->count())->toBe(2);
});

test('a volunteer can cancel a draft reservation', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);

    $volunteer = volunteerFor($edition);
    $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlot));

    $response = $this->actingAs($volunteer)->delete(route('planning.cancel', $missionSlot));

    $response->assertRedirect();
    expect(VolunteerAssignment::where('user_id', $volunteer->id)->count())->toBe(0);
});

test('reservation and cancellation are blocked once the planning is validated', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);

    $volunteer = volunteerFor($edition, ['is_validated' => true, 'validated_at' => now()]);
    $assignment = VolunteerAssignment::factory()->validated()->create([
        'user_id' => $volunteer->id,
        'mission_slot_id' => $missionSlot->id,
    ]);

    $reserveResponse = $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlot));
    $reserveResponse->assertSessionHas('error');

    $cancelResponse = $this->actingAs($volunteer)->delete(route('planning.cancel', $missionSlot));
    $cancelResponse->assertSessionHas('error');

    expect($assignment->fresh()->status)->toBe('validated');
});

test('finalizing requires at least the edition\'s minimum slots per volunteer', function () {
    $edition = Edition::factory()->create(['status' => 'active', 'min_slots_per_volunteer' => 1]);
    $volunteer = volunteerFor($edition);

    $response = $this->actingAs($volunteer)->post(route('planning.finalize'));

    $response->assertSessionHas('error');
    expect($volunteer->editions()->first()->pivot->is_validated)->toBeFalse();
});

test('finalizing validates and locks the planning', function () {
    $edition = Edition::factory()->create(['status' => 'active', 'min_slots_per_volunteer' => 1]);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);

    $volunteer = volunteerFor($edition);
    $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlot));

    $response = $this->actingAs($volunteer)->post(route('planning.finalize'));

    $response->assertRedirect(route('planning.index'));

    $assignment = VolunteerAssignment::where('user_id', $volunteer->id)->firstOrFail();
    expect($assignment->status)->toBe('validated');

    $pivot = $volunteer->editions()->first()->pivot;
    expect($pivot->is_validated)->toBeTrue()
        ->and($pivot->validated_at)->not->toBeNull()
        ->and($pivot->badge_uid)->not->toBeNull();

    expect($volunteer->fresh()->profile_locked_at)->not->toBeNull();
});

test('a minor cannot finalize their planning until their profile is validated', function (?string $minorValidatedAt, bool $expectedValidated) {
    $edition = Edition::factory()->create(['status' => 'active', 'min_slots_per_volunteer' => 1]);
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3]);

    $volunteer = volunteerFor($edition);
    $volunteer->forceFill(['is_minor' => true, 'minor_validated_at' => $minorValidatedAt])->save();
    $this->actingAs($volunteer)->post(route('planning.reserve', $missionSlot));

    $response = $this->actingAs($volunteer)->post(route('planning.finalize'));

    if (! $expectedValidated) {
        $response->assertSessionHas('error');
    }

    expect((bool) $volunteer->editions()->first()->pivot->is_validated)->toBe($expectedValidated);
})->with([
    'awaiting validation' => [null, false],
    'validated by an admin' => ['2026-09-01 10:00:00', true],
]);
