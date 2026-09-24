<?php

use App\Mail\InvitationCodeMail;
use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

uses(RefreshDatabase::class);

function candidatesCsv(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('candidats.csv', $content);
}

test('an admin can import candidates from a CSV file, which creates and sends a code to each of them', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);

    $response = $this->actingAs($admin)->post(route('admin.invitation-codes.import.store'), [
        'file' => candidatesCsv("E-mail;Nom\nalice@example.com;Alice\nbob@example.com;Bob\n"),
    ]);

    $response->assertRedirect(route('admin.invitation-codes.index'));
    $response->assertSessionHas('status', '2 code(s) créé(s) et envoyé(s).');

    expect(InvitationCode::where('edition_id', $edition->id)->where('status', 'pending')->pluck('email')->sort()->values()->all())
        ->toBe(['alice@example.com', 'bob@example.com']);

    Mail::assertQueued(InvitationCodeMail::class, 2);
    Mail::assertQueued(InvitationCodeMail::class, fn ($mail) => $mail->hasTo('alice@example.com'));
});

test('an admin can import candidates from an Excel file', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    Edition::factory()->create(['status' => 'active']);

    $path = tempnam(sys_get_temp_dir(), 'candidats').'.xlsx';
    $writer = new XlsxWriter;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(['E-mail']));
    $writer->addRow(Row::fromValues(['camille@example.com']));
    $writer->close();

    $this->actingAs($admin)->post(route('admin.invitation-codes.import.store'), [
        'file' => new UploadedFile($path, 'candidats.xlsx', null, null, true),
    ])->assertSessionHas('status', '1 code(s) créé(s) et envoyé(s).');

    expect(InvitationCode::where('email', 'camille@example.com')->exists())->toBeTrue();
});

test('the import skips invalid, duplicate and already invited e-mails and reports why', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    User::factory()->create(['email' => 'inscrit@example.com'])->editions()->attach($edition->id);
    User::factory()->create(['email' => 'ancien@example.com'])->editions()->attach(Edition::factory()->create(['status' => 'archived'])->id);
    User::factory()->admin()->create(['email' => 'orga@example.com']);
    InvitationCode::factory()->for($edition)->create(['email' => 'attente@example.com']);

    $response = $this->actingAs($admin)->post(route('admin.invitation-codes.import.store'), [
        'file' => candidatesCsv("nouveau@example.com\npas-un-email\nNOUVEAU@example.com\ninscrit@example.com\nattente@example.com\nancien@example.com\norga@example.com\n"),
    ]);

    $response->assertSessionHas('status', '2 code(s) créé(s) et envoyé(s).');
    $response->assertSessionHas('importSkipped', [
        ['line' => 2, 'value' => 'pas-un-email', 'reason' => 'Adresse e-mail invalide'],
        ['line' => 3, 'value' => 'NOUVEAU@example.com', 'reason' => 'En double dans le fichier'],
        ['line' => 4, 'value' => 'inscrit@example.com', 'reason' => "Déjà inscrit(e) à l'édition en cours"],
        ['line' => 5, 'value' => 'attente@example.com', 'reason' => 'Un code est déjà en attente pour cet e-mail'],
        ['line' => 7, 'value' => 'orga@example.com', 'reason' => "Adresse d'un compte administrateur"],
    ]);

    expect(InvitationCode::where('email', 'attente@example.com')->count())->toBe(1)
        ->and(InvitationCode::where('email', 'ancien@example.com')->exists())->toBeTrue();
    Mail::assertQueued(InvitationCodeMail::class, 2);
});

test('the import rejects a file that is neither Excel nor CSV', function () {
    Edition::factory()->create(['status' => 'active']);

    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.invitation-codes.import.store'), [
        'file' => UploadedFile::fake()->create('candidats.pdf', 10, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors(['file' => 'Le fichier doit être au format Excel (.xlsx) ou CSV (.csv).']);
});

test('the import rejects a file without any e-mail', function () {
    Edition::factory()->create(['status' => 'active']);

    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.invitation-codes.import.store'), [
        'file' => candidatesCsv("E-mail\n"),
    ]);

    $response->assertSessionHas('error', 'Aucune adresse e-mail trouvée dans la première colonne du fichier.');
    expect(InvitationCode::count())->toBe(0);
});

test('the import rejects a file with more than 500 lines', function () {
    Mail::fake();

    Edition::factory()->create(['status' => 'active']);
    $lines = collect(range(1, 501))->map(fn (int $index) => "candidat{$index}@example.com")->join("\n");

    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.invitation-codes.import.store'), [
        'file' => candidatesCsv($lines),
    ]);

    $response->assertSessionHas('error');
    expect(InvitationCode::count())->toBe(0);
    Mail::assertNothingQueued();
});

test('a volunteer cannot import candidates', function () {
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs(User::factory()->create())
        ->post(route('admin.invitation-codes.import.store'), ['file' => candidatesCsv("alice@example.com\n")])
        ->assertForbidden();

    expect(InvitationCode::count())->toBe(0);
});
