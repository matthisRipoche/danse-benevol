<?php

use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function registerVolunteer(Edition $edition, array $userAttributes = []): User
{
    $volunteer = User::factory()->create($userAttributes);
    $volunteer->editions()->attach($edition->id);

    return $volunteer;
}

test('the volunteer list shows minor status and a validate action', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);

    registerVolunteer($edition, ['first_name' => 'Camille', 'last_name' => 'Pending', 'is_minor' => true, 'minor_validated_at' => null]);
    registerVolunteer($edition, ['first_name' => 'Alex', 'last_name' => 'Done', 'is_minor' => true, 'minor_validated_at' => now()]);
    registerVolunteer($edition, ['first_name' => 'Sam', 'last_name' => 'Adult', 'is_minor' => false]);

    $response = $this->actingAs($admin)->get(route('admin.volunteers.index'));

    $response->assertOk();
    $response->assertSee('à valider');
    $response->assertSee('validé le');
});

test('an admin can validate a minor profile', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $minor = registerVolunteer($edition, ['is_minor' => true, 'minor_validated_at' => null]);

    $response = $this->actingAs($admin)->post(route('admin.volunteers.validate-minor', $minor));

    $response->assertRedirect();
    expect($minor->fresh()->minor_validated_at)->not->toBeNull();

    expect(AuditLog::where('action', 'user.minor_validated')
        ->where('subject_id', $minor->id)
        ->exists())->toBeTrue();
});

test('validating a non-minor fails gracefully', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = registerVolunteer($edition, ['is_minor' => false]);

    $response = $this->actingAs($admin)->post(route('admin.volunteers.validate-minor', $volunteer));

    $response->assertSessionHas('error');
    expect($volunteer->fresh()->minor_validated_at)->toBeNull();
});

test('validating an already validated minor fails gracefully', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    $validatedAt = now()->subDay();
    $minor = registerVolunteer($edition, ['is_minor' => true, 'minor_validated_at' => $validatedAt]);

    $response = $this->actingAs($admin)->post(route('admin.volunteers.validate-minor', $minor));

    $response->assertSessionHas('error');
    expect($minor->fresh()->minor_validated_at->timestamp)->toBe($validatedAt->timestamp);
});

test('validating a volunteer not registered for the active edition returns 404', function () {
    $admin = User::factory()->admin()->create();
    Edition::factory()->create(['status' => 'active']);
    $notRegistered = User::factory()->create(['is_minor' => true]);

    $response = $this->actingAs($admin)->post(route('admin.volunteers.validate-minor', $notRegistered));

    $response->assertNotFound();
});

test('the mineur filter narrows results to unvalidated or validated minors', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(['status' => 'active']);
    registerVolunteer($edition, ['first_name' => 'Camille', 'last_name' => 'Pending', 'is_minor' => true, 'minor_validated_at' => null]);
    registerVolunteer($edition, ['first_name' => 'Alex', 'last_name' => 'Done', 'is_minor' => true, 'minor_validated_at' => now()]);

    $pending = $this->actingAs($admin)->get(route('admin.volunteers.index', ['mineur' => 'a_valider']));
    $pending->assertSee('Camille');
    $pending->assertDontSee('Alex');

    $validated = $this->actingAs($admin)->get(route('admin.volunteers.index', ['mineur' => 'valide']));
    $validated->assertSee('Alex');
    $validated->assertDontSee('Camille');
});

test('a volunteer cannot validate a minor profile', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = registerVolunteer($edition);
    $minor = registerVolunteer($edition, ['is_minor' => true]);

    $response = $this->actingAs($volunteer)->post(route('admin.volunteers.validate-minor', $minor));

    $response->assertForbidden();
});

test('a guest is redirected to the login page', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $minor = registerVolunteer($edition, ['is_minor' => true]);

    $response = $this->post(route('admin.volunteers.validate-minor', $minor));

    $response->assertRedirect(route('login'));
});
