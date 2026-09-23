<?php

use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function validRegistrationPayload(InvitationCode $code, array $overrides = []): array
{
    return array_merge([
        'code' => $code->code,
        'first_name' => 'Camille',
        'last_name' => 'Dupont',
        'email' => 'camille.dupont@example.com',
        'phone' => '0612345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'photo' => UploadedFile::fake()->image('photo.jpg'),
    ], $overrides);
}

test('an already authenticated volunteer visiting the registration page is redirected to their planning', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/inscription');

    $response->assertRedirect(route('planning.index'));
});

test('a volunteer can register with a valid pending invitation code', function () {
    Storage::fake('local');

    $edition = Edition::factory()->create();
    $code = InvitationCode::factory()->for($edition)->create();

    $response = $this->post('/inscription', validRegistrationPayload($code));

    $response->assertRedirect(route('planning.index'));
    $this->assertAuthenticated();

    $user = User::where('email', 'camille.dupont@example.com')->firstOrFail();

    expect($user->role)->toBe('volunteer')
        ->and($user->photo_path)->not->toBeNull()
        ->and($user->editions->pluck('id'))->toContain($edition->id);

    Storage::disk('local')->assertExists($user->photo_path);

    expect($code->fresh())
        ->status->toBe('used')
        ->used_by_user_id->toBe($user->id);
});

test('registration fails with a non-existent invitation code', function () {
    $response = $this->post('/inscription', validRegistrationPayload(
        InvitationCode::factory()->make(['code' => 'UNKNOWN1'])
    ));

    $response->assertSessionHasErrors('code');
    expect(User::count())->toBe(0);
});

test('registration fails with an expired invitation code', function () {
    $code = InvitationCode::factory()->expired()->create();

    $response = $this->post('/inscription', validRegistrationPayload($code));

    $response->assertSessionHasErrors('code');
    expect(User::count())->toBe(0);
});

test('registration fails with an already used invitation code', function () {
    $code = InvitationCode::factory()->used()->create();

    $response = $this->post('/inscription', validRegistrationPayload($code));

    $response->assertSessionHasErrors('code');
    expect(User::where('email', 'camille.dupont@example.com')->exists())->toBeFalse();
});

test('registration fails with a revoked invitation code', function () {
    $code = InvitationCode::factory()->revoked()->create();

    $response = $this->post('/inscription', validRegistrationPayload($code));

    $response->assertSessionHasErrors('code');
    expect(User::count())->toBe(0);
});

test('registration fails when the email does not match the code\'s pinned email', function () {
    $code = InvitationCode::factory()->create(['email' => 'pinned@example.com']);

    $response = $this->post('/inscription', validRegistrationPayload($code, [
        'email' => 'someone-else@example.com',
    ]));

    $response->assertSessionHasErrors('email');
    expect(User::count())->toBe(0);
});

test('registration fails when the email is already taken', function () {
    User::factory()->create(['email' => 'camille.dupont@example.com']);
    $code = InvitationCode::factory()->create();

    $response = $this->post('/inscription', validRegistrationPayload($code));

    $response->assertSessionHasErrors('email');
    expect(User::count())->toBe(1);
});

test('registration requires all fields including the photo', function () {
    $response = $this->post('/inscription', []);

    $response->assertSessionHasErrors([
        'code', 'first_name', 'last_name', 'email', 'phone', 'password', 'photo',
    ]);
});

test('an invitation code cannot be reused for a second registration', function () {
    Storage::fake('local');

    $code = InvitationCode::factory()->create();

    $this->post('/inscription', validRegistrationPayload($code));
    $this->post('/deconnexion');

    $response = $this->post('/inscription', validRegistrationPayload($code, [
        'email' => 'second-attempt@example.com',
    ]));

    $response->assertSessionHasErrors('code');
    expect(User::count())->toBe(1);
});

test('a volunteer who checks the under-18 box is registered as a minor awaiting validation', function () {
    Storage::fake('local');

    $code = InvitationCode::factory()->for(Edition::factory())->create();

    $this->post('/inscription', validRegistrationPayload($code, ['is_minor' => '1']));

    $user = User::where('email', 'camille.dupont@example.com')->firstOrFail();

    expect($user->is_minor)->toBeTrue()
        ->and($user->minor_validated_at)->toBeNull();
});

test('a volunteer who leaves the under-18 box unchecked is not registered as a minor', function () {
    Storage::fake('local');

    $code = InvitationCode::factory()->for(Edition::factory())->create();

    $this->post('/inscription', validRegistrationPayload($code));

    expect(User::where('email', 'camille.dupont@example.com')->firstOrFail()->is_minor)->toBeFalse();
});
