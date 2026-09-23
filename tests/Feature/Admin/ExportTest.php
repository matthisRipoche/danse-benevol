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
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

uses(RefreshDatabase::class);

/**
 * @return array{0: Edition, 1: User}
 */
function editionWithOneAssignment(string $missionName = 'Accueil exposants'): array
{
    $edition = Edition::factory()->create(['status' => 'active', 'slug' => 'salon-2027']);
    $volunteer = User::factory()->create([
        'first_name' => 'Camille',
        'last_name' => 'Dupont',
        'email' => 'camille@example.com',
        'phone' => '0612345678',
    ]);
    $volunteer->editions()->attach($edition->id);

    $day = EventDay::factory()->for($edition)->create(['label' => 'Samedi', 'date' => '2027-05-15']);
    $timeSlot = TimeSlot::factory()->for($day, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00']);
    $mission = Mission::factory()->for($edition)->create(['name' => $missionName]);
    $missionSlot = MissionSlot::factory()->for($mission, 'mission')->for($timeSlot, 'timeSlot')->create();

    VolunteerAssignment::factory()->for($volunteer)->for($missionSlot)->create(['status' => 'validated']);

    return [$edition, $volunteer];
}

/**
 * Read back every sheet of a streamed XLSX export.
 *
 * @return array<string, list<list<mixed>>>
 */
function readXlsxSheets(string $content): array
{
    $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
    file_put_contents($path, $content);

    $reader = new XlsxReader;
    $reader->open($path);
    $sheets = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $sheets[$sheet->getName()][] = $row->toArray();
        }
    }

    $reader->close();

    return $sheets;
}

test('an admin can download the general planning as a CSV file that Excel opens in French', function () {
    editionWithOneAssignment();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.exports.download', ['planning', 'csv']));

    $response->assertOk();
    $response->assertDownload('salon-2027-planning-'.now()->format('Y-m-d').'.csv');

    $lines = explode("\n", trim($response->streamedContent()));

    expect($lines[0])->toBe("\u{FEFF}Jour;Date;Début;Fin;Mission;\"Poste restreint\";Nom;Prénom;E-mail;Téléphone;Statut")
        ->and($lines[1])->toBe('Samedi;15/05/2027;10:00;12:00;"Accueil exposants";Non;Dupont;Camille;camille@example.com;0612345678;Validé');
});

test('the mission lists export has one Excel sheet per mission, with an Excel-safe name', function () {
    [$edition] = editionWithOneAssignment('Accueil / Billetterie');
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.exports.download', ['missions', 'xlsx']));

    $response->assertOk();
    $response->assertDownload('salon-2027-missions-'.now()->format('Y-m-d').'.xlsx');

    expect(readXlsxSheets($response->streamedContent()))->toBe([
        'Accueil Billetterie' => [
            ['Mission', 'Jour', 'Date', 'Début', 'Fin', 'Nom', 'Prénom', 'Téléphone', 'E-mail', 'Statut'],
            ['Accueil / Billetterie', 'Samedi', '15/05/2027', '10:00', '12:00', 'Dupont', 'Camille', '0612345678', 'camille@example.com', 'Validé'],
        ],
    ]);
});

test('the contacts export lists each volunteer with their minor and planning status', function () {
    [$edition, $volunteer] = editionWithOneAssignment();
    $volunteer->forceFill(['is_minor' => true])->save();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.exports.download', ['contacts', 'xlsx']));

    expect(readXlsxSheets($response->streamedContent())['Contacts'][1])
        ->toBe(['Dupont', 'Camille', 'camille@example.com', '0612345678', 'Oui (à valider)', 'En attente', 1, 'Accueil exposants']);
});

test('each export download is recorded in the audit log', function () {
    [$edition] = editionWithOneAssignment();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.exports.download', ['contacts', 'csv']))->streamedContent();

    $log = AuditLog::where('action', 'export.downloaded')->firstOrFail();

    expect($log->admin_id)->toBe($admin->id)
        ->and($log->subject_id)->toBe($edition->id)
        ->and($log->changes)->toBe(['type' => 'contacts', 'format' => 'csv']);
});

test('an unknown export type or format returns 404', function (string $type, string $format) {
    editionWithOneAssignment();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.exports.download', [$type, $format]))
        ->assertNotFound();
})->with([
    'unknown type' => ['badges', 'xlsx'],
    'unknown format' => ['planning', 'pdf'],
]);

test('a volunteer cannot download an export', function () {
    [, $volunteer] = editionWithOneAssignment();

    $this->actingAs($volunteer)
        ->get(route('admin.exports.download', ['contacts', 'csv']))
        ->assertForbidden();
});
