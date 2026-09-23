<?php

use App\Models\Edition;
use App\Models\EventDay;
use App\Models\Mission;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function missionsCsv(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('missions.csv', $content);
}

test('an admin can import missions, each opened on every time slot with its capacity', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $day = EventDay::factory()->for($edition)->create();
    TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'position' => 1]);
    TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '14:00:00', 'ends_at' => '16:00:00', 'position' => 2]);

    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.missions.import.store'), [
        'file' => missionsCsv("Mission;Description;Publique;Capacité\nVestiaires;Niveau -1;Oui;4\nBilletterie;;Non;2\nPoint Info;;;3\n"),
    ]);

    $response->assertRedirect(route('admin.missions.index'));
    $response->assertSessionHas('status', '3 mission(s) importée(s).');

    $missions = Mission::where('edition_id', $edition->id)->with('missionSlots')->orderBy('name')->get();

    expect($missions->map(fn (Mission $mission) => [
        $mission->name,
        $mission->description,
        $mission->is_public,
        $mission->default_capacity,
        $mission->missionSlots->pluck('capacity')->all(),
    ])->all())->toBe([
        ['Billetterie', null, false, 2, [2, 2]],
        ['Point Info', null, true, 3, [3, 3]],
        ['Vestiaires', 'Niveau -1', true, 4, [4, 4]],
    ]);
});

test('the import skips invalid lines and existing missions and reports why', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    Mission::factory()->for($edition)->create(['name' => 'Vestiaires']);

    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.missions.import.store'), [
        'file' => missionsCsv("Loges;;Oui;3\n;Sans nom;Oui;2\nvestiaires;;Oui;2\nLOGES;;Oui;2\nScène;;Peut-être;2\nAccueil;;Oui;beaucoup\n"),
    ]);

    $response->assertSessionHas('status', '1 mission(s) importée(s).');
    $response->assertSessionHas('importSkipped', [
        ['line' => 2, 'value' => '(vide)', 'reason' => 'Nom de mission manquant'],
        ['line' => 3, 'value' => 'vestiaires', 'reason' => 'Une mission porte déjà ce nom'],
        ['line' => 4, 'value' => 'LOGES', 'reason' => 'Une mission porte déjà ce nom'],
        ['line' => 5, 'value' => 'Scène', 'reason' => 'Colonne « Publique » invalide (« Peut-être ») : Oui ou Non attendu'],
        ['line' => 6, 'value' => 'Accueil', 'reason' => 'Capacité manquante ou invalide (nombre de 0 à 500)'],
    ]);

    expect(Mission::where('edition_id', $edition->id)->pluck('name')->sort()->values()->all())->toBe(['Loges', 'Vestiaires']);
});

test('the missions import rejects a file that is neither Excel nor CSV', function () {
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.missions.import.store'), ['file' => UploadedFile::fake()->create('missions.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors(['file' => 'Le fichier doit être au format Excel (.xlsx) ou CSV (.csv).']);
});

test('a volunteer cannot import missions', function () {
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs(User::factory()->create())
        ->post(route('admin.missions.import.store'), ['file' => missionsCsv("Vestiaires;;Oui;4\n")])
        ->assertForbidden();

    expect(Mission::count())->toBe(0);
});
