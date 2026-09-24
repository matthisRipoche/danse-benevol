<?php

use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A volunteer of the archived 2027 edition, locked and validated as a minor back then,
 * with a pending code for the active 2028 edition.
 *
 * @return array{0: User, 1: Edition, 2: InvitationCode}
 */
function returningVolunteerWithCode(array $codeAttributes = []): array
{
    $previous = Edition::factory()->create(['status' => 'archived', 'name' => 'Salon de la Danse 2027']);
    $current = Edition::factory()->create(['status' => 'active', 'name' => 'Salon de la Danse 2028']);

    $volunteer = User::factory()->create([
        'email' => 'ancien@example.fr',
        'is_minor' => true,
        'minor_validated_at' => now()->subYear(),
        'profile_locked_at' => now()->subYear(),
    ]);
    $volunteer->editions()->attach($previous->id, ['is_validated' => true, 'validated_at' => now()->subYear()]);

    $code = InvitationCode::factory()->for($current)->create([
        'code' => 'RETOUR28',
        'email' => 'ancien@example.fr',
        'status' => 'pending',
        'expires_at' => now()->addDays(7),
        ...$codeAttributes,
    ]);

    return [$volunteer, $current, $code];
}

test('the link from the email sends a logged-out volunteer to the login page, then back to the join form', function () {
    [$volunteer] = returningVolunteerWithCode();
    $joinUrl = route('edition.join', ['code' => 'RETOUR28']);

    $this->get($joinUrl)->assertRedirect(route('login'));

    $this->post(route('login'), ['email' => $volunteer->email, 'password' => 'password'])
        ->assertRedirect($joinUrl);

    $this->get($joinUrl)
        ->assertOk()
        ->assertSee('Salon de la Danse 2028')
        ->assertSee('value="RETOUR28"', false);
});

test('a returning volunteer joins the new edition with their code', function () {
    [$volunteer, $current, $code] = returningVolunteerWithCode();

    $this->actingAs($volunteer)
        ->post(route('edition.join.store'), ['code' => 'retour28', 'is_minor' => '0'])
        ->assertRedirect(route('planning.index'))
        ->assertSessionHas('status', 'Bienvenue pour Salon de la Danse 2028 ! Tu peux composer ton planning ci-dessous.');

    $volunteer->refresh();
    expect($volunteer->isRegisteredFor($current))->toBeTrue()
        ->and($volunteer->editions()->count())->toBe(2)
        ->and($code->fresh()->status)->toBe('used')
        ->and($code->fresh()->used_by_user_id)->toBe($volunteer->id)
        ->and($volunteer->profile_locked_at)->toBeNull()
        ->and($volunteer->is_minor)->toBeFalse()
        ->and($volunteer->minor_validated_at)->toBeNull();

    $this->actingAs($volunteer)->get(route('planning.index'))->assertOk();
});

test('a volunteer still minor must be validated again for the new edition', function () {
    [$volunteer] = returningVolunteerWithCode();

    $this->actingAs($volunteer)->post(route('edition.join.store'), ['code' => 'RETOUR28', 'is_minor' => '1']);

    $volunteer->refresh();
    expect($volunteer->is_minor)->toBeTrue()
        ->and($volunteer->minor_validated_at)->toBeNull();
});

test('the join form refuses invalid codes', function (array $codeAttributes, string $message) {
    [$volunteer, $current] = returningVolunteerWithCode($codeAttributes);

    $this->actingAs($volunteer)
        ->post(route('edition.join.store'), ['code' => 'RETOUR28'])
        ->assertSessionHasErrors(['code' => $message]);

    expect($volunteer->fresh()->isRegisteredFor($current))->toBeFalse()
        ->and($volunteer->fresh()->profile_locked_at)->not->toBeNull();
})->with([
    'code envoyé à une autre adresse' => [['email' => 'autre@example.fr'], 'Ce code a été envoyé à une autre adresse e-mail que celle de ton compte.'],
    'code expiré' => [['expires_at' => now()->subDay()], "Ce code d'invitation a expiré."],
    'code déjà utilisé' => [['status' => 'used'], "Ce code d'invitation a déjà été utilisé ou n'est plus valide."],
    'code révoqué' => [['status' => 'revoked'], "Ce code d'invitation a déjà été utilisé ou n'est plus valide."],
]);

test('an unknown code is refused', function () {
    [$volunteer] = returningVolunteerWithCode();

    $this->actingAs($volunteer)
        ->post(route('edition.join.store'), ['code' => 'INCONNU1'])
        ->assertSessionHasErrors(['code' => "Ce code d'invitation est introuvable."]);
});

test('a volunteer already registered for the edition is sent to their planning', function () {
    [$volunteer, $current] = returningVolunteerWithCode();
    $volunteer->editions()->attach($current->id);

    $this->actingAs($volunteer)->get(route('edition.join'))->assertRedirect(route('planning.index'));

    $this->actingAs($volunteer)
        ->post(route('edition.join.store'), ['code' => 'RETOUR28'])
        ->assertSessionHasErrors(['code' => 'Tu es déjà inscrit(e) à cette édition.']);
});

test('the registration page tells a returning volunteer to log in instead of creating an account', function () {
    returningVolunteerWithCode();

    $this->get(route('register', ['code' => 'RETOUR28']))
        ->assertOk()
        ->assertSee('Tu as déjà un compte bénévole.')
        ->assertSee(e(route('edition.join', ['code' => 'RETOUR28'])), false);

    $this->get(route('register', ['code' => 'INCONNU1']))
        ->assertOk()
        ->assertDontSee('Tu as déjà un compte bénévole.');
});

test('guests cannot join an edition', function () {
    returningVolunteerWithCode();

    $this->post(route('edition.join.store'), ['code' => 'RETOUR28'])->assertRedirect(route('login'));
});
