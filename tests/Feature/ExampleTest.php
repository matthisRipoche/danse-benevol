<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest visiting the app root is redirected to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

test('an authenticated volunteer visiting the app root is redirected to their planning', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('planning.index'));
});

test('an authenticated admin visiting the app root is redirected to the invitation codes list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/');

    $response->assertRedirect(route('admin.invitation-codes.index'));
});
