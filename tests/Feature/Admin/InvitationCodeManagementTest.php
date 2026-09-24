<?php

use App\Mail\InvitationCodeMail;
use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('an admin can view the list of invitation codes for the active edition', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $code = InvitationCode::factory()->for($edition)->create(['email' => 'candidat@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.invitation-codes.index'));

    $response->assertOk();
    $response->assertSee('candidat@example.com');
    $response->assertSee($code->code);
    $response->assertSee('Déconnexion');
});

test('an admin can create an invitation code for the active edition', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);

    $response = $this->actingAs($admin)->post(route('admin.invitation-codes.store'), [
        'email' => 'candidat@example.com',
    ]);

    $response->assertRedirect(route('admin.invitation-codes.index'));

    $code = InvitationCode::where('email', 'candidat@example.com')->firstOrFail();

    expect($code->edition_id)->toBe($edition->id)
        ->and($code->status)->toBe('pending')
        ->and($code->created_by_id)->toBe($admin->id);

    expect(AuditLog::where('action', 'invitation_code.created')
        ->where('subject_id', $code->id)
        ->exists())->toBeTrue();

    Mail::assertQueued(InvitationCodeMail::class, fn (InvitationCodeMail $mail) => $mail->hasTo('candidat@example.com')
        && $mail->invitationCode->is($code)
    );
});

test('creating an invitation code requires a valid email', function () {
    $admin = User::factory()->admin()->create();
    Edition::factory()->create(['status' => 'active']);

    $response = $this->actingAs($admin)->post(route('admin.invitation-codes.store'), [
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('an invitation code cannot be created for a volunteer already registered for the active edition', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    User::factory()->create(['email' => 'candidat@example.com'])->editions()->attach($edition->id);

    $response = $this->actingAs($admin)->post(route('admin.invitation-codes.store'), [
        'email' => 'candidat@example.com',
    ]);

    $response->assertSessionHasErrors(['email' => "Ce bénévole est déjà inscrit à l'édition en cours."]);
    Mail::assertNothingSent();
});

test('an invitation code cannot be created for an admin account', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs($admin)
        ->post(route('admin.invitation-codes.store'), ['email' => 'admin@example.com'])
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

test('a volunteer from a previous edition can be invited and receives a « welcome back » email', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $previous = Edition::factory()->create(['status' => 'archived']);
    Edition::factory()->create(['status' => 'active', 'name' => 'Salon de la Danse 2028']);
    User::factory()->create(['email' => 'ancien@example.com'])->editions()->attach($previous->id);

    $this->actingAs($admin)
        ->post(route('admin.invitation-codes.store'), ['email' => 'ancien@example.com'])
        ->assertSessionHasNoErrors();

    $code = InvitationCode::where('email', 'ancien@example.com')->firstOrFail();

    Mail::assertQueued(InvitationCodeMail::class, function (InvitationCodeMail $mail) use ($code) {
        $html = $mail->render();

        return str_contains($html, 'Content de te revoir')
            && str_contains($html, 'Salon de la Danse 2028')
            && str_contains($html, e(route('edition.join', ['code' => $code->code])))
            && ! str_contains($html, 'Créer mon compte');
    });
});

test('an invitation code cannot be created for an email with an already pending code on the edition', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    InvitationCode::factory()->for($edition)->create(['email' => 'candidat@example.com']);

    $response = $this->actingAs($admin)->post(route('admin.invitation-codes.store'), [
        'email' => 'candidat@example.com',
    ]);

    $response->assertSessionHasErrors('email');
    expect(InvitationCode::where('email', 'candidat@example.com')->count())->toBe(1);
});

test('an admin can revoke a pending invitation code', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $code = InvitationCode::factory()->for($edition)->create();

    $response = $this->actingAs($admin)->post(route('admin.invitation-codes.revoke', $code));

    $response->assertRedirect(route('admin.invitation-codes.index'));
    expect($code->fresh()->status)->toBe('revoked');

    expect(AuditLog::where('action', 'invitation_code.revoked')
        ->where('subject_id', $code->id)
        ->exists())->toBeTrue();
});

test('an already used invitation code cannot be revoked', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $code = InvitationCode::factory()->used()->for($edition)->create();

    $this->actingAs($admin)->post(route('admin.invitation-codes.revoke', $code));

    expect($code->fresh()->status)->toBe('used');
});

test('a volunteer cannot access the admin invitation codes area', function () {
    $volunteer = User::factory()->create();
    Edition::factory()->create(['status' => 'active']);

    $response = $this->actingAs($volunteer)->get(route('admin.invitation-codes.index'));

    $response->assertForbidden();
});

test('a guest is redirected to the login page', function () {
    Edition::factory()->create(['status' => 'active']);

    $response = $this->get(route('admin.invitation-codes.index'));

    $response->assertRedirect(route('login'));
});

test('a new invitation code is valid for 7 days and the email says until when', function () {
    Mail::fake();
    $this->freezeTime();

    $admin = User::factory()->admin()->create();
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs($admin)->post(route('admin.invitation-codes.store'), ['email' => 'candidat@example.com']);

    $code = InvitationCode::where('email', 'candidat@example.com')->firstOrFail();
    expect($code->expires_at->toDateTimeString())->toBe(now()->addDays(7)->toDateTimeString());

    Mail::assertQueued(InvitationCodeMail::class, fn (InvitationCodeMail $mail) => str_contains(
        $mail->render(),
        "valable jusqu'au ".now()->addDays(7)->format('d/m/Y à H:i'),
    ));
});
