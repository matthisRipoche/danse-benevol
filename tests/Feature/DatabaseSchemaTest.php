<?php

use App\Models\Edition;
use App\Models\EditionVolunteer;
use App\Models\EventDay;
use App\Models\InvitationCode;
use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an invitation code links to its edition and the user who used it', function () {
    $edition = Edition::factory()->create();
    $volunteer = User::factory()->create();

    $code = InvitationCode::factory()->used()->for($edition)->create([
        'used_by_user_id' => $volunteer->id,
    ]);

    expect($code->edition->is($edition))->toBeTrue()
        ->and($code->usedBy->is($volunteer))->toBeTrue();
});

test('a mission slot exposes its remaining capacity as assignments are made', function () {
    $edition = Edition::factory()->create();
    $day = EventDay::factory()->for($edition)->create();
    $slot = TimeSlot::factory()->for($day, 'eventDay')->create();
    $mission = Mission::factory()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission)->for($slot, 'timeSlot')->create(['capacity' => 2]);

    expect($missionSlot->remainingCapacity())->toBe(2);

    VolunteerAssignment::factory()->create([
        'mission_slot_id' => $missionSlot->id,
        'time_slot_id' => $slot->id,
    ]);

    expect($missionSlot->fresh()->remainingCapacity())->toBe(1);
});

test('a volunteer cannot have two assignments on the same time slot', function () {
    $day = EventDay::factory()->create();
    $slot = TimeSlot::factory()->for($day, 'eventDay')->create();
    $user = User::factory()->create();

    $firstMissionSlot = MissionSlot::factory()->create(['time_slot_id' => $slot->id]);
    $secondMissionSlot = MissionSlot::factory()->create(['time_slot_id' => $slot->id]);

    VolunteerAssignment::factory()->create([
        'user_id' => $user->id,
        'mission_slot_id' => $firstMissionSlot->id,
        'time_slot_id' => $slot->id,
    ]);

    VolunteerAssignment::factory()->create([
        'user_id' => $user->id,
        'mission_slot_id' => $secondMissionSlot->id,
        'time_slot_id' => $slot->id,
    ]);
})->throws(QueryException::class);

test('a volunteer has a single participation record per edition', function () {
    $edition = Edition::factory()->create();
    $user = User::factory()->create();

    EditionVolunteer::factory()->validated()->for($user)->for($edition)->create();

    expect($user->editions()->first()->pivot->is_validated)->toBeTrue();
});
