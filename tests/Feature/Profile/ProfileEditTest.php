<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * @return array<string, string>
 */
function validProfilePayload(array $overrides = []): array
{
    return [
        'first_name' => 'Léa',
        'last_name' => 'Martin',
        'email' => 'lea.martin@example.fr',
        'phone' => '06 12 34 56 78',
        ...$overrides,
    ];
}

test('a volunteer with an unlocked profile sees the edit form', function () {
    $volunteer = User::factory()->create(['first_name' => 'Camille', 'profile_locked_at' => null]);

    $this->actingAs($volunteer)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Modifier mes informations')
        ->assertSee('value="Camille"', false);
});

test('a volunteer can update their personal information', function () {
    $volunteer = User::factory()->create(['profile_locked_at' => null]);

    $this->actingAs($volunteer)
        ->put(route('profile.update'), validProfilePayload())
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('status');

    $volunteer->refresh();
    expect($volunteer->first_name)->toBe('Léa')
        ->and($volunteer->last_name)->toBe('Martin')
        ->and($volunteer->email)->toBe('lea.martin@example.fr')
        ->and($volunteer->phone)->toBe('06 12 34 56 78');
});

test('a new photo replaces the previous one on the private disk', function () {
    Storage::fake('local');
    Storage::disk('local')->put('photos/old.jpg', 'old');
    $volunteer = User::factory()->create(['profile_locked_at' => null, 'photo_path' => 'photos/old.jpg']);

    $this->actingAs($volunteer)
        ->put(route('profile.update'), validProfilePayload(['photo' => UploadedFile::fake()->image('new.jpg')]))
        ->assertRedirect(route('profile.show'));

    $newPhotoPath = $volunteer->fresh()->photo_path;
    expect($newPhotoPath)->not->toBe('photos/old.jpg');
    Storage::disk('local')->assertExists($newPhotoPath);
    Storage::disk('local')->assertMissing('photos/old.jpg');
});

test('the current photo is kept when no new photo is sent', function () {
    $volunteer = User::factory()->create(['profile_locked_at' => null, 'photo_path' => 'photos/current.jpg']);

    $this->actingAs($volunteer)->put(route('profile.update'), validProfilePayload());

    expect($volunteer->fresh()->photo_path)->toBe('photos/current.jpg');
});

test('a volunteer cannot change their minor status', function () {
    $volunteer = User::factory()->create(['profile_locked_at' => null, 'is_minor' => true]);

    $this->actingAs($volunteer)->put(route('profile.update'), validProfilePayload(['is_minor' => '0']));

    expect($volunteer->fresh()->is_minor)->toBeTrue();
});

test('the email must stay unique', function () {
    User::factory()->create(['email' => 'taken@example.fr']);
    $volunteer = User::factory()->create(['profile_locked_at' => null]);

    $this->actingAs($volunteer)
        ->put(route('profile.update'), validProfilePayload(['email' => 'taken@example.fr']))
        ->assertSessionHasErrors('email');
});

test('a volunteer can keep their own email', function () {
    $volunteer = User::factory()->create(['profile_locked_at' => null, 'email' => 'same@example.fr']);

    $this->actingAs($volunteer)
        ->put(route('profile.update'), validProfilePayload(['email' => 'same@example.fr']))
        ->assertSessionHasNoErrors();
});

test('a locked profile still lets the volunteer edit their name, phone and photo', function () {
    Storage::fake('local');
    $volunteer = User::factory()->create(['profile_locked_at' => now(), 'email' => 'locked@example.fr']);

    $this->actingAs($volunteer)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Verrouillée depuis la validation du planning');

    $this->actingAs($volunteer)
        ->put(route('profile.update'), validProfilePayload(['photo' => UploadedFile::fake()->image('new.jpg')]))
        ->assertRedirect(route('profile.show'))
        ->assertSessionHasNoErrors();

    $volunteer->refresh();
    expect($volunteer->first_name)->toBe('Léa')
        ->and($volunteer->last_name)->toBe('Martin')
        ->and($volunteer->phone)->toBe('06 12 34 56 78');
    Storage::disk('local')->assertExists($volunteer->photo_path);
});

test('a locked profile keeps its email even if one is sent', function () {
    $volunteer = User::factory()->create(['profile_locked_at' => now(), 'email' => 'locked@example.fr']);

    $this->actingAs($volunteer)->put(route('profile.update'), validProfilePayload(['email' => 'new@example.fr']));

    expect($volunteer->fresh()->email)->toBe('locked@example.fr');
});

test('guests are redirected to the login page', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->put(route('profile.update'), validProfilePayload())->assertRedirect(route('login'));
});
