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

function activeEditionOfMay2027(): Edition
{
    return Edition::factory()->create(['status' => 'active', 'start_date' => '2027-05-14', 'end_date' => '2027-05-16']);
}

test('an admin can add a day of the edition, labelled with its weekday', function () {
    $edition = activeEditionOfMay2027();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.schedule.days.store'), ['date' => '2027-05-15'])
        ->assertRedirect(route('admin.schedule.index'));

    $day = EventDay::where('edition_id', $edition->id)->firstOrFail();

    expect($day->date->format('Y-m-d'))->toBe('2027-05-15')
        ->and($day->label)->toBe('Samedi');
});

test('a day must be within the edition dates and not already exist', function (string $date, string $message) {
    $edition = activeEditionOfMay2027();
    EventDay::factory()->for($edition)->create(['date' => '2027-05-14', 'label' => 'Vendredi']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.schedule.days.store'), ['date' => $date])
        ->assertSessionHasErrors(['date' => $message]);
})->with([
    'before the edition' => ['2027-05-13', "Le jour doit être compris dans les dates de l'édition (du 14/05/2027 au 16/05/2027)."],
    'after the edition' => ['2027-05-17', "Le jour doit être compris dans les dates de l'édition (du 14/05/2027 au 16/05/2027)."],
    'already existing' => ['2027-05-14', 'Ce jour existe déjà.'],
]);

test('adding a time slot keeps positions chronological and opens it for missions with a default capacity', function () {
    $edition = activeEditionOfMay2027();
    $day = EventDay::factory()->for($edition)->create(['date' => '2027-05-15', 'label' => 'Samedi']);
    $afternoon = TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '14:00:00', 'ends_at' => '16:00:00', 'position' => 1]);
    $vestiaires = Mission::factory()->for($edition)->create(['default_capacity' => 4]);
    $closedMission = Mission::factory()->for($edition)->create(['default_capacity' => 0]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.schedule.time-slots.store', $day), ['starts_at' => '10:00', 'ends_at' => '12:00'])
        ->assertRedirect(route('admin.schedule.index'));

    $morning = TimeSlot::where('event_day_id', $day->id)->where('starts_at', '10:00:00')->firstOrFail();

    expect($morning->position)->toBe(1)
        ->and($afternoon->fresh()->position)->toBe(2)
        ->and(MissionSlot::where('time_slot_id', $morning->id)->where('mission_id', $vestiaires->id)->value('capacity'))->toBe(4)
        ->and(MissionSlot::where('time_slot_id', $morning->id)->where('mission_id', $closedMission->id)->exists())->toBeFalse();
});

test('a time slot must end after it starts and not overlap another one of the day', function (string $startsAt, string $endsAt, string $field, string $message) {
    $edition = activeEditionOfMay2027();
    $day = EventDay::factory()->for($edition)->create(['date' => '2027-05-15', 'label' => 'Samedi']);
    TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'position' => 1]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.schedule.time-slots.store', $day), ['starts_at' => $startsAt, 'ends_at' => $endsAt])
        ->assertSessionHasErrors([$field => $message]);

    expect(TimeSlot::where('event_day_id', $day->id)->count())->toBe(1);
})->with([
    'ends before it starts' => ['16:00', '14:00', 'ends_at', "L'heure de fin doit être après l'heure de début."],
    'overlaps an existing slot' => ['11:00', '13:00', 'starts_at', 'Ce créneau chevauche un créneau existant de la même journée.'],
]);

test('a time slot touching another one does not overlap it', function () {
    $edition = activeEditionOfMay2027();
    $day = EventDay::factory()->for($edition)->create(['date' => '2027-05-15', 'label' => 'Samedi']);
    TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'position' => 1]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.schedule.time-slots.store', $day), ['starts_at' => '12:00', 'ends_at' => '14:00'])
        ->assertSessionHasNoErrors();

    expect(TimeSlot::where('event_day_id', $day->id)->count())->toBe(2);
});

test('deleting a time slot renumbers the remaining ones', function () {
    $edition = activeEditionOfMay2027();
    $day = EventDay::factory()->for($edition)->create(['date' => '2027-05-15', 'label' => 'Samedi']);
    $morning = TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'position' => 1]);
    $afternoon = TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '14:00:00', 'ends_at' => '16:00:00', 'position' => 2]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.schedule.time-slots.destroy', $morning))
        ->assertRedirect(route('admin.schedule.index'));

    expect(TimeSlot::find($morning->id))->toBeNull()
        ->and($afternoon->fresh()->position)->toBe(1);
});

test('a time slot or a day with booked volunteers cannot be deleted', function () {
    $edition = activeEditionOfMay2027();
    $day = EventDay::factory()->for($edition)->create(['date' => '2027-05-15', 'label' => 'Samedi']);
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'position' => 1]);
    $missionSlot = MissionSlot::factory()->for(Mission::factory()->for($edition))->for($timeSlot, 'timeSlot')->create();
    VolunteerAssignment::factory()->for($missionSlot)->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->delete(route('admin.schedule.time-slots.destroy', $timeSlot))->assertSessionHas('error');
    $this->actingAs($admin)->delete(route('admin.schedule.days.destroy', $day))->assertSessionHas('error');

    expect(TimeSlot::find($timeSlot->id))->not->toBeNull()
        ->and(EventDay::find($day->id))->not->toBeNull();
});

test('a day without bookings can be deleted with its time slots', function () {
    $edition = activeEditionOfMay2027();
    $day = EventDay::factory()->for($edition)->create(['date' => '2027-05-15', 'label' => 'Samedi']);
    TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'position' => 1]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.schedule.days.destroy', $day))
        ->assertRedirect(route('admin.schedule.index'));

    expect(EventDay::find($day->id))->toBeNull()
        ->and(TimeSlot::where('event_day_id', $day->id)->exists())->toBeFalse();
});

test('a volunteer cannot manage days and time slots', function () {
    activeEditionOfMay2027();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.schedule.days.store'), ['date' => '2027-05-15'])
        ->assertForbidden();

    expect(EventDay::count())->toBe(0);
});

test('the schedule page lists the days with their time slots and bookings', function () {
    $edition = activeEditionOfMay2027();
    $day = EventDay::factory()->for($edition)->create(['date' => '2027-05-15', 'label' => 'Samedi']);
    TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'position' => 1]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.schedule.index'))
        ->assertOk()
        ->assertSeeInOrder(['Samedi', '15/05/2027', '10:00–12:00', '0 inscrit(s)']);
});
