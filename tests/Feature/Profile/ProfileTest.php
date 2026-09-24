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

function profileVolunteerFor(Edition $edition, array $pivot = [], array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->editions()->attach($edition->id, $pivot);

    return $user;
}

function assignMission(User $volunteer, Edition $edition, string $missionName, string $startsAt, string $endsAt): void
{
    $day = EventDay::factory()->for($edition)->create();
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => $startsAt, 'ends_at' => $endsAt]);
    $mission = Mission::factory()->for($edition)->create(['name' => $missionName]);
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create();

    VolunteerAssignment::factory()->for($volunteer)->for($missionSlot)->create(['status' => 'validated']);
}

test('a validated volunteer sees their badge, missions and total engagement time', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = profileVolunteerFor($edition, [
        'is_validated' => true,
        'validated_at' => now(),
        'badge_uid' => 'abcd1234-0000-0000-0000-000000000000',
    ], ['first_name' => 'Camille', 'last_name' => 'Dupont']);

    assignMission($volunteer, $edition, 'Accueil exposants', '10:00:00', '12:00:00');
    assignMission($volunteer, $edition, 'Vestiaires', '14:00:00', '15:30:00');

    $response = $this->actingAs($volunteer)->get(route('profile.show'));

    $response->assertOk()
        ->assertSee('Planning validé &amp; badge actif', false)
        ->assertSee('Camille Dupont')
        ->assertSee('SDLD-ABCD1234')
        ->assertSee('QR code du badge SDLD-ABCD1234')
        ->assertSee('Accueil exposants')
        ->assertSee('Vestiaires')
        ->assertSee('3 h30');
});

test('a volunteer whose planning is not validated sees a pending badge without an id', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = profileVolunteerFor($edition);

    $response = $this->actingAs($volunteer)->get(route('profile.show'));

    $response->assertOk()
        ->assertSee('Badge en attente')
        ->assertSee('ID attribué à la validation')
        ->assertSee('QR code généré à la validation')
        ->assertDontSee('SDLD-');
});

test('a volunteer not registered for the active edition is sent to the join page from the profile', function () {
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs(User::factory()->create())
        ->get(route('profile.show'))
        ->assertRedirect(route('edition.join'));
});

test('an admin without volunteer registration still cannot see the volunteer profile page', function () {
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('profile.show'))
        ->assertForbidden();
});

test('guests are redirected to the login page', function () {
    $this->get(route('profile.show'))->assertRedirect(route('login'));
});

test('a volunteer can load their own photo from the private disk', function () {
    Storage::fake('local');

    $photoPath = UploadedFile::fake()->image('photo.jpg')->store('photos', 'local');
    $volunteer = User::factory()->create(['photo_path' => $photoPath]);

    $this->actingAs($volunteer)
        ->get(route('profile.photo'))
        ->assertOk();
});

test('the photo route returns 404 when the volunteer has no photo', function () {
    Storage::fake('local');

    $volunteer = User::factory()->create(['photo_path' => null]);

    $this->actingAs($volunteer)
        ->get(route('profile.photo'))
        ->assertNotFound();
});
