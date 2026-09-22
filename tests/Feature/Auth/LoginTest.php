<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a volunteer is redirected to their planning after login', function () {
    $user = User::factory()->create();

    $response = $this->post('/connexion', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('planning.index'));
    $this->assertAuthenticatedAs($user);
});

test('an admin is redirected to the invitation codes list after login', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->post('/connexion', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.invitation-codes.index'));
});

test('login fails with a wrong password', function () {
    $user = User::factory()->create();

    $response = $this->post('/connexion', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('login fails with an unknown email', function () {
    $response = $this->post('/connexion', [
        'email' => 'unknown@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('logging out redirects to the login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/deconnexion');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('an already authenticated volunteer visiting the login page is redirected to their planning', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/connexion');

    $response->assertRedirect(route('planning.index'));
});

test('an already authenticated admin visiting the login page is redirected to the invitation codes list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/connexion');

    $response->assertRedirect(route('admin.invitation-codes.index'));
});

test('the dev admin login button is hidden outside the local environment', function () {
    $response = $this->get('/connexion');

    $response->assertDontSee('Connexion rapide admin');
});

test('the dev admin login route is unavailable outside the local environment', function () {
    $response = $this->post('/connexion/dev-admin');

    $response->assertNotFound();
    $this->assertGuest();
});

test('the dev admin login button and route work in the local environment', function () {
    app()->instance('env', 'local');

    $admin = User::factory()->admin()->create();

    $this->get('/connexion')->assertSee('Connexion rapide admin');

    $response = $this->withoutMiddleware(PreventRequestForgery::class)
        ->post('/connexion/dev-admin');

    $response->assertRedirect(route('admin.invitation-codes.index'));
    $this->assertAuthenticatedAs($admin);
});

test('the dev admin login route fails gracefully when no admin exists', function () {
    app()->instance('env', 'local');

    $response = $this->withoutMiddleware(PreventRequestForgery::class)
        ->post('/connexion/dev-admin');

    $response->assertNotFound();
    $this->assertGuest();
});
