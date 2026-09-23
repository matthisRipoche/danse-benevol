<?php

use App\Models\Edition;
use App\Models\EventDay;
use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * @return array{0: Edition, 1: User}
 */
function volunteerWithSchedule(): array
{
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Dupont']);
    $volunteer->editions()->attach($edition->id);

    $day = EventDay::factory()->for($edition)->create(['label' => 'Samedi']);
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00']);
    $mission = Mission::factory()->for($edition)->create(['name' => 'Accueil exposants']);
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create();

    VolunteerAssignment::factory()->for($volunteer)->for($missionSlot)->create();

    return [$edition, $volunteer];
}

test('the volunteer list shows each volunteer\'s mission times and links to their detail page', function () {
    [, $volunteer] = volunteerWithSchedule();

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.volunteers.index'));

    $response->assertOk()
        ->assertSeeInOrder(['Sam.', '10:00–12:00', 'Accueil exposants'])
        ->assertSee(route('admin.volunteers.show', $volunteer));
});

test('an admin can see a volunteer\'s detail with their planning', function () {
    [, $volunteer] = volunteerWithSchedule();

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.volunteers.show', $volunteer));

    $response->assertOk()
        ->assertSee('Camille Dupont')
        ->assertSee($volunteer->email)
        ->assertSeeInOrder(['Samedi', '10:00–12:00', 'Accueil exposants', 'Brouillon'])
        ->assertSee('2 h');
});

test('the detail page returns 404 for a user not registered for the active edition', function () {
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.volunteers.show', User::factory()->create()))
        ->assertNotFound();
});

test('a volunteer cannot access another volunteer\'s detail page or photo', function () {
    [$edition, $volunteer] = volunteerWithSchedule();
    $otherVolunteer = User::factory()->create();
    $otherVolunteer->editions()->attach($edition->id);

    $this->actingAs($otherVolunteer)->get(route('admin.volunteers.show', $volunteer))->assertForbidden();
    $this->actingAs($otherVolunteer)->get(route('admin.volunteers.photo', $volunteer))->assertForbidden();
});

test('an admin can load a volunteer\'s photo', function () {
    Storage::fake('local');

    $volunteer = User::factory()->create([
        'photo_path' => UploadedFile::fake()->image('photo.jpg')->store('photos', 'local'),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.volunteers.photo', $volunteer))
        ->assertOk();
});
