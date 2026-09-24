<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('the login page links to the forgotten password form', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('password.request'));

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Mot de passe oublié');
});

test('a reset link is emailed to an existing account', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('an unknown email gets the same answer and no email is sent', function () {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'nobody@example.fr'])
        ->assertRedirect()
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

test('the reset email is in French and points to the reset form', function () {
    $user = User::factory()->create(['first_name' => 'Camille', 'email' => 'camille@example.fr']);

    $mail = (new ResetPasswordNotification('the-token'))->toMail($user);

    expect($mail->subject)->toBe('Réinitialisation de ton mot de passe — Salon de la Danse');

    $html = (string) $mail->render();
    expect($html)->toContain('Bonjour Camille')
        ->toContain('Choisir un nouveau mot de passe')
        ->toContain('60 minutes')
        ->toContain(e(route('password.reset', ['token' => 'the-token', 'email' => 'camille@example.fr'])));
});

test('the reset form is prefilled with the email from the link', function () {
    $this->get(route('password.reset', ['token' => 'the-token', 'email' => 'camille@example.fr']))
        ->assertOk()
        ->assertSee('value="camille@example.fr"', false)
        ->assertSee('value="the-token"', false);
});

test('a valid link lets the user choose a new password and log in with it', function () {
    $user = User::factory()->create(['remember_token' => 'old-remember-token']);
    $token = Password::createToken($user);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nouveau-mot-de-passe',
        'password_confirmation' => 'nouveau-mot-de-passe',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $user->refresh();
    expect(Hash::check('nouveau-mot-de-passe', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe('old-remember-token');

    $this->post(route('login'), ['email' => $user->email, 'password' => 'nouveau-mot-de-passe'])
        ->assertRedirect(route('planning.index'));
});

test('an invalid token does not change the password', function () {
    $user = User::factory()->create();

    $this->post(route('password.store'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'nouveau-mot-de-passe',
        'password_confirmation' => 'nouveau-mot-de-passe',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('an expired link does not change the password', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->travel(61)->minutes();

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nouveau-mot-de-passe',
        'password_confirmation' => 'nouveau-mot-de-passe',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('a link can only be used once', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $payload = [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nouveau-mot-de-passe',
        'password_confirmation' => 'nouveau-mot-de-passe',
    ];

    $this->post(route('password.store'), $payload)->assertRedirect(route('login'));

    $this->post(route('password.store'), [...$payload, 'password' => 'encore-autre-chose', 'password_confirmation' => 'encore-autre-chose'])
        ->assertSessionHasErrors('email');

    expect(Hash::check('nouveau-mot-de-passe', $user->fresh()->password))->toBeTrue();
});

test('the new password must be confirmed and long enough', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'court',
        'password_confirmation' => 'autre',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('a logged-in user cannot reach the reset pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('password.request'))->assertRedirect();
    $this->actingAs($user)->get(route('password.reset', ['token' => 'the-token']))->assertRedirect();
});
