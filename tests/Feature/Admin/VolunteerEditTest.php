<?php

use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function editableVolunteerFor(Edition $edition, array $attributes = []): User
{
    $volunteer = User::factory()->create($attributes);
    $volunteer->editions()->attach($edition->id);

    return $volunteer;
}

/**
 * @return array<string, string>
 */
function validVolunteerPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Léa',
        'last_name' => 'Martin',
        'email' => 'lea.martin@example.fr',
        'phone' => '06 12 34 56 78',
        ...$overrides,
    ];
}

test('an admin sees the edit form of a volunteer', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = editableVolunteerFor($edition, ['first_name' => 'Camille']);

    $this->actingAs($admin)
        ->get(route('admin.volunteers.edit', $volunteer))
        ->assertOk()
        ->assertSee('value="Camille"', false);
});

test('an admin can edit a locked profile and the change is audited', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = editableVolunteerFor($edition, ['profile_locked_at' => now(), 'phone' => '0600000000']);

    $this->actingAs($admin)
        ->put(route('admin.volunteers.update', $volunteer), validVolunteerPayload())
        ->assertRedirect(route('admin.volunteers.show', $volunteer))
        ->assertSessionHas('status');

    $volunteer->refresh();
    expect($volunteer->first_name)->toBe('Léa')
        ->and($volunteer->phone)->toBe('06 12 34 56 78')
        ->and($volunteer->profile_locked_at)->not->toBeNull();

    $auditLog = AuditLog::where('action', 'user.profile_updated')->where('subject_id', $volunteer->id)->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->admin_id)->toBe($admin->id)
        ->and($auditLog->changes['fields'])->toContain('first_name', 'phone');
});

test('an admin can replace the badge photo', function () {
    Storage::fake('local');
    Storage::disk('local')->put('photos/old.jpg', 'old');
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = editableVolunteerFor($edition, ['photo_path' => 'photos/old.jpg']);

    $this->actingAs($admin)->put(
        route('admin.volunteers.update', $volunteer),
        validVolunteerPayload(['photo' => UploadedFile::fake()->image('new.jpg')]),
    );

    Storage::disk('local')->assertExists($volunteer->fresh()->photo_path);
    Storage::disk('local')->assertMissing('photos/old.jpg');
    expect(AuditLog::where('action', 'user.profile_updated')->first()->changes['fields'])->toContain('photo_path');
});

test('unchecking the minor box clears the minor validation', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = editableVolunteerFor($edition, ['is_minor' => true, 'minor_validated_at' => now()]);

    $this->actingAs($admin)->put(route('admin.volunteers.update', $volunteer), validVolunteerPayload());

    $volunteer->refresh();
    expect($volunteer->is_minor)->toBeFalse()
        ->and($volunteer->minor_validated_at)->toBeNull();
});

test('an admin can declare a volunteer as minor', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = editableVolunteerFor($edition, ['is_minor' => false]);

    $this->actingAs($admin)->put(route('admin.volunteers.update', $volunteer), validVolunteerPayload(['is_minor' => '1']));

    $volunteer->refresh();
    expect($volunteer->is_minor)->toBeTrue()
        ->and($volunteer->minor_validated_at)->toBeNull();
});

test('saving without any change does not write an audit entry', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = editableVolunteerFor($edition, validVolunteerPayload(['is_minor' => false]));

    $this->actingAs($admin)->put(route('admin.volunteers.update', $volunteer), validVolunteerPayload());

    expect(AuditLog::where('action', 'user.profile_updated')->exists())->toBeFalse();
});

test('the email must not belong to another account', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.fr']);
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = editableVolunteerFor($edition);

    $this->actingAs($admin)
        ->put(route('admin.volunteers.update', $volunteer), validVolunteerPayload(['email' => 'admin@example.fr']))
        ->assertSessionHasErrors('email');
});

test('a volunteer not registered for the active edition returns 404', function () {
    $admin = User::factory()->admin()->create();
    Edition::factory()->create(['status' => 'active']);
    $notRegistered = User::factory()->create();

    $this->actingAs($admin)->get(route('admin.volunteers.edit', $notRegistered))->assertNotFound();
    $this->actingAs($admin)->put(route('admin.volunteers.update', $notRegistered), validVolunteerPayload())->assertNotFound();
});

test('a volunteer cannot edit another volunteer through the admin routes', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $intruder = editableVolunteerFor($edition);
    $volunteer = editableVolunteerFor($edition, ['first_name' => 'Camille']);

    $this->actingAs($intruder)->get(route('admin.volunteers.edit', $volunteer))->assertForbidden();
    $this->actingAs($intruder)->put(route('admin.volunteers.update', $volunteer), validVolunteerPayload())->assertForbidden();

    expect($volunteer->fresh()->first_name)->toBe('Camille');
});
