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
 * An active edition with one day and two time slots.
 *
 * @return array{0: Edition, 1: TimeSlot, 2: TimeSlot}
 */
function editionWithTwoTimeSlots(): array
{
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    $morning = TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'position' => 1]);
    $afternoon = TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '14:00:00', 'ends_at' => '16:00:00', 'position' => 2]);

    return [$edition, $morning, $afternoon];
}

test('an admin sees the missions of the active edition', function () {
    [$edition] = editionWithTwoTimeSlots();
    Mission::factory()->for($edition)->create(['name' => 'Vestiaires']);
    Mission::factory()->for($edition)->create(['name' => 'Billetterie', 'is_public' => false]);
    Mission::factory()->create(['name' => 'Mission d\'une autre édition']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.missions.index'))
        ->assertOk()
        ->assertSeeInOrder(['Vestiaires', 'Billetterie', 'Restreinte'])
        ->assertDontSee('Mission d\'une autre édition');
});

test('creating a mission opens it on every time slot with its default capacity', function () {
    [$edition, $morning, $afternoon] = editionWithTwoTimeSlots();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.missions.store'), [
        'name' => 'Vestiaires',
        'description' => 'Niveau -1',
        'is_public' => '1',
        'default_capacity' => 4,
    ]);

    $response->assertRedirect(route('admin.missions.index'));

    $mission = Mission::where('name', 'Vestiaires')->firstOrFail();

    expect($mission->edition_id)->toBe($edition->id)
        ->and($mission->default_capacity)->toBe(4)
        ->and($mission->missionSlots()->orderBy('time_slot_id')->get(['time_slot_id', 'capacity'])->toArray())->toBe([
            ['time_slot_id' => $morning->id, 'capacity' => 4],
            ['time_slot_id' => $afternoon->id, 'capacity' => 4],
        ])
        ->and(AuditLog::where('action', 'mission.created')->where('subject_id', $mission->id)->exists())->toBeTrue();
});

test('a mission created with 0 places is not opened on any time slot', function () {
    editionWithTwoTimeSlots();

    $this->actingAs(User::factory()->admin()->create())->post(route('admin.missions.store'), [
        'name' => 'Vestiaires',
        'is_public' => '1',
        'default_capacity' => 0,
    ]);

    expect(Mission::where('name', 'Vestiaires')->firstOrFail()->missionSlots()->count())->toBe(0);
});

test('two missions of the same edition cannot share a name', function () {
    [$edition] = editionWithTwoTimeSlots();
    Mission::factory()->for($edition)->create(['name' => 'Vestiaires']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.missions.store'), ['name' => 'Vestiaires', 'is_public' => '1', 'default_capacity' => 3])
        ->assertSessionHasErrors(['name' => 'Une mission porte déjà ce nom sur cette édition.']);
});

test('updating a mission adjusts, closes and opens its time slots', function () {
    [$edition, $morning, $afternoon] = editionWithTwoTimeSlots();
    $evening = TimeSlot::factory()->for($morning->eventDay, 'eventDay')->create(['starts_at' => '16:00:00', 'ends_at' => '18:00:00', 'position' => 3]);
    $mission = Mission::factory()->for($edition)->create(['name' => 'Vestiaires', 'default_capacity' => 4]);
    MissionSlot::factory()->for($mission)->for($morning, 'timeSlot')->create(['capacity' => 4]);
    MissionSlot::factory()->for($mission)->for($afternoon, 'timeSlot')->create(['capacity' => 4]);

    $response = $this->actingAs(User::factory()->admin()->create())->put(route('admin.missions.update', $mission), [
        'name' => 'Vestiaires artistes',
        'description' => '',
        'is_public' => '0',
        'default_capacity' => 2,
        'capacities' => [$morning->id => 6, $afternoon->id => 0, $evening->id => 3],
    ]);

    $response->assertRedirect(route('admin.missions.index'));

    expect($mission->fresh())
        ->name->toBe('Vestiaires artistes')
        ->is_public->toBeFalse()
        ->default_capacity->toBe(2)
        ->and($mission->missionSlots()->orderBy('time_slot_id')->get(['time_slot_id', 'capacity'])->toArray())->toBe([
            ['time_slot_id' => $morning->id, 'capacity' => 6],
            ['time_slot_id' => $evening->id, 'capacity' => 3],
        ]);
});

test('a time slot capacity cannot go below the number of booked volunteers', function () {
    [$edition, $morning] = editionWithTwoTimeSlots();
    $mission = Mission::factory()->for($edition)->create(['name' => 'Vestiaires']);
    $missionSlot = MissionSlot::factory()->for($mission)->for($morning, 'timeSlot')->create(['capacity' => 4]);
    VolunteerAssignment::factory()->count(2)->for($missionSlot)->create();

    $response = $this->actingAs(User::factory()->admin()->create())->put(route('admin.missions.update', $mission), [
        'name' => 'Vestiaires',
        'is_public' => '1',
        'default_capacity' => 4,
        'capacities' => [$morning->id => 1],
    ]);

    $response->assertSessionHasErrors(["capacities.{$morning->id}"]);
    expect($missionSlot->fresh()->capacity)->toBe(4);
});

test('a mission without bookings can be deleted', function () {
    [$edition, $morning] = editionWithTwoTimeSlots();
    $mission = Mission::factory()->for($edition)->create();
    MissionSlot::factory()->for($mission)->for($morning, 'timeSlot')->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.missions.destroy', $mission))
        ->assertRedirect(route('admin.missions.index'));

    expect(Mission::find($mission->id))->toBeNull()
        ->and(MissionSlot::where('mission_id', $mission->id)->exists())->toBeFalse();
});

test('a mission with booked volunteers cannot be deleted', function () {
    [$edition, $morning] = editionWithTwoTimeSlots();
    $mission = Mission::factory()->for($edition)->create();
    $missionSlot = MissionSlot::factory()->for($mission)->for($morning, 'timeSlot')->create();
    VolunteerAssignment::factory()->for($missionSlot)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.missions.destroy', $mission))
        ->assertSessionHas('error');

    expect(Mission::find($mission->id))->not->toBeNull();
});

test('a mission of another edition cannot be edited', function () {
    editionWithTwoTimeSlots();
    $otherMission = Mission::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.missions.edit', $otherMission))
        ->assertNotFound();
});

test('a volunteer cannot manage missions', function () {
    editionWithTwoTimeSlots();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.missions.store'), ['name' => 'Vestiaires', 'is_public' => '1', 'default_capacity' => 3])
        ->assertForbidden();

    expect(Mission::count())->toBe(0);
});

test('the mission edit page shows the capacity and bookings of each time slot', function () {
    [$edition, $morning, $afternoon] = editionWithTwoTimeSlots();
    $mission = Mission::factory()->for($edition)->create(['name' => 'Vestiaires']);
    $missionSlot = MissionSlot::factory()->for($mission)->for($morning, 'timeSlot')->create(['capacity' => 4]);
    VolunteerAssignment::factory()->for($missionSlot)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.missions.edit', $mission))
        ->assertOk()
        ->assertSeeInOrder(['10:00–12:00', 'value="4"', '1 inscrit(s)', '14:00–16:00', 'value="0"', '0 inscrit(s)'], false);
});
