<?php

use App\Models\User;
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
