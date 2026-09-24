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

uses(RefreshDatabase::class);

/**
 * @return array{0: Edition, 1: MissionSlot}
 */
function adultOnlySlot(array $missionAttributes = [], bool $isPublic = true): array
{
    $edition = Edition::factory()->create(['status' => 'active']);
    $timeSlot = TimeSlot::factory()->for(EventDay::factory()->for($edition), 'eventDay')->create(['position' => 1]);
    $mission = Mission::factory()->adultOnly()->for($edition)->create(['is_public' => $isPublic, 'name' => 'Caisse', ...$missionAttributes]);

    return [$edition, MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create(['capacity' => 3])];
}

function editionVolunteer(Edition $edition, array $attributes = []): User
{
    $volunteer = User::factory()->create($attributes);
    $volunteer->editions()->attach($edition->id);

    return $volunteer;
}

test('a minor cannot book a mission forbidden to minors', function () {
    [$edition, $missionSlot] = adultOnlySlot();
    $minor = editionVolunteer($edition, ['is_minor' => true, 'minor_validated_at' => now()]);

    $this->actingAs($minor)
        ->post(route('planning.reserve', $missionSlot))
        ->assertSessionHas('error', 'Cette mission est interdite aux mineurs.');

    expect(VolunteerAssignment::where('user_id', $minor->id)->exists())->toBeFalse();
});

test('an adult can book a mission forbidden to minors', function () {
    [$edition, $missionSlot] = adultOnlySlot();
    $adult = editionVolunteer($edition, ['is_minor' => false]);

    $this->actingAs($adult)
        ->post(route('planning.reserve', $missionSlot))
        ->assertSessionHas('status', 'Créneau réservé.');
});

test('a minor can still book a mission open to minors', function () {
    [$edition, $missionSlot] = adultOnlySlot(['is_adult_only' => false]);
    $minor = editionVolunteer($edition, ['is_minor' => true]);

    $this->actingAs($minor)
        ->post(route('planning.reserve', $missionSlot))
        ->assertSessionHas('status', 'Créneau réservé.');
});

test('a minor sees the mission as reserved to adults, without a booking button', function () {
    [$edition, $missionSlot] = adultOnlySlot();
    $minor = editionVolunteer($edition, ['is_minor' => true]);

    $this->actingAs($minor)
        ->get(route('planning.index'))
        ->assertOk()
        ->assertSee('Réservée aux majeurs')
        ->assertDontSee(route('planning.reserve', $missionSlot));
});

test('an adult sees the booking button of a mission forbidden to minors', function () {
    [$edition, $missionSlot] = adultOnlySlot();
    $adult = editionVolunteer($edition, ['is_minor' => false]);

    $this->actingAs($adult)
        ->get(route('planning.index'))
        ->assertOk()
        ->assertDontSee('Réservée aux majeurs')
        ->assertSee(route('planning.reserve', $missionSlot));
});

test('an admin cannot assign a minor to a restricted post forbidden to minors', function () {
    [$edition, $missionSlot] = adultOnlySlot(isPublic: false);
    $admin = User::factory()->admin()->create();
    $minor = editionVolunteer($edition, ['is_minor' => true, 'email' => 'mineur@example.fr']);

    $this->actingAs($admin)
        ->post(route('admin.restricted-missions.assign', $missionSlot), ['email' => 'mineur@example.fr'])
        ->assertSessionHas('error', 'Ce poste est interdit aux mineurs.');

    expect(VolunteerAssignment::where('user_id', $minor->id)->exists())->toBeFalse();
});

test('an admin can mark a mission as forbidden to minors, and it is shown in the list', function () {
    [$edition] = adultOnlySlot(['is_adult_only' => false, 'name' => 'Vestiaires']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.missions.store'), [
        'name' => 'Logistique (Niveau -2)',
        'is_public' => '1',
        'is_adult_only' => '1',
        'default_capacity' => 4,
    ])->assertRedirect(route('admin.missions.index'));

    expect(Mission::where('name', 'Logistique (Niveau -2)')->value('is_adult_only'))->toBeTrue()
        ->and(Mission::where('name', 'Vestiaires')->value('is_adult_only'))->toBeFalse();

    $this->actingAs($admin)
        ->get(route('admin.missions.index'))
        ->assertSeeInOrder(['Logistique (Niveau -2)', 'Interdite aux mineurs', 'Vestiaires']);
});

test('updating a mission can lift the restriction', function () {
    [$edition, $missionSlot] = adultOnlySlot();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put(route('admin.missions.update', $missionSlot->mission), [
        'name' => 'Caisse',
        'is_public' => '1',
        'is_adult_only' => '0',
        'default_capacity' => 3,
    ])->assertRedirect(route('admin.missions.index'));

    expect($missionSlot->mission->fresh()->is_adult_only)->toBeFalse();
});

test('forbidding a mission to minors warns about minors already booked on it', function () {
    [$edition, $missionSlot] = adultOnlySlot(['is_adult_only' => false]);
    $admin = User::factory()->admin()->create();
    $minor = editionVolunteer($edition, ['is_minor' => true]);
    VolunteerAssignment::factory()->for($minor)->for($missionSlot)->create(['time_slot_id' => $missionSlot->time_slot_id]);

    $this->actingAs($admin)->put(route('admin.missions.update', $missionSlot->mission), [
        'name' => 'Caisse',
        'is_public' => '1',
        'is_adult_only' => '1',
        'default_capacity' => 3,
    ])->assertSessionHas('status', fn (string $status) => str_contains($status, '1 bénévole(s) mineur(s) déjà inscrit(s)'));
});

test('the import reads the optional « Interdite aux mineurs » column', function () {
    adultOnlySlot(['is_adult_only' => false, 'name' => 'Existante']);
    $admin = User::factory()->admin()->create();
    $csv = "Mission;Description;Publique;Capacité;Interdite aux mineurs\nVestiaires;Niveau -1;Oui;4;\nBilletterie;;Non;2;Oui\nPoint Info;;Oui;3;Peut-être\n";

    $this->actingAs($admin)->post(route('admin.missions.import.store'), [
        'file' => UploadedFile::fake()->createWithContent('missions.csv', $csv),
    ])->assertRedirect(route('admin.missions.index'));

    expect(Mission::where('name', 'Vestiaires')->value('is_adult_only'))->toBeFalse()
        ->and(Mission::where('name', 'Billetterie')->value('is_adult_only'))->toBeTrue()
        ->and(Mission::where('name', 'Point Info')->exists())->toBeFalse();
});
