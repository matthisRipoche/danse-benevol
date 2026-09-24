<?php

use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\EventDay;
use App\Models\InvitationCode;
use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function editionPayload(array $overrides = []): array
{
    return [
        'name' => 'Salon de la Danse 2028',
        'start_date' => '2028-05-12',
        'end_date' => '2028-05-14',
        'min_slots_per_volunteer' => 1,
        'max_slots_per_volunteer' => 3,
        'max_consecutive_slots' => 2,
        ...$overrides,
    ];
}

test('an admin sees every edition with its status and figures', function () {
    $admin = User::factory()->admin()->create();
    $active = Edition::factory()->create(['name' => 'Salon 2027', 'status' => 'active', 'start_date' => '2027-05-14', 'end_date' => '2027-05-16']);
    Edition::factory()->create(['name' => 'Salon 2026', 'status' => 'archived', 'start_date' => '2026-05-15', 'end_date' => '2026-05-17']);
    Mission::factory()->count(2)->for($active)->create();
    User::factory()->create()->editions()->attach($active->id);

    $this->actingAs($admin)
        ->get(route('admin.editions.index'))
        ->assertOk()
        ->assertSeeInOrder(['Salon 2027', 'Active', 'Salon 2026', 'Archivée']);
});

test('the dashboard links to the editions page', function () {
    $admin = User::factory()->admin()->create();
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee(route('admin.editions.index'));
});

test('volunteers and guests cannot manage editions', function () {
    $edition = Edition::factory()->create(['status' => 'active']);
    $volunteer = User::factory()->create();

    $this->actingAs($volunteer)->get(route('admin.editions.index'))->assertForbidden();
    $this->actingAs($volunteer)->post(route('admin.editions.store'), editionPayload())->assertForbidden();
    $this->actingAs($volunteer)->post(route('admin.editions.activate', $edition))->assertForbidden();
    auth()->logout();
    $this->get(route('admin.editions.index'))->assertRedirect(route('login'));
});

test('a new edition is created as a draft without changing the active edition', function () {
    $admin = User::factory()->admin()->create();
    $current = Edition::factory()->create(['status' => 'active']);

    $this->actingAs($admin)
        ->post(route('admin.editions.store'), editionPayload())
        ->assertRedirect(route('admin.editions.index'));

    $edition = Edition::where('name', 'Salon de la Danse 2028')->firstOrFail();
    expect($edition->status)->toBe('draft')
        ->and($edition->slug)->toBe('salon-de-la-danse-2028')
        ->and($edition->start_date->format('Y-m-d'))->toBe('2028-05-12')
        ->and(Edition::active()->id)->toBe($current->id)
        ->and(AuditLog::where('action', 'edition.created')->where('subject_id', $edition->id)->exists())->toBeTrue();
});

test('the slug stays unique', function () {
    $admin = User::factory()->admin()->create();
    Edition::factory()->create(['name' => 'Ancien nom', 'slug' => 'salon-de-la-danse-2028']);

    $this->actingAs($admin)->post(route('admin.editions.store'), editionPayload());

    expect(Edition::where('name', 'Salon de la Danse 2028')->value('slug'))->toBe('salon-de-la-danse-2028-2');
});

test('invalid editions are refused', function (array $overrides, string $field) {
    $admin = User::factory()->admin()->create();
    Edition::factory()->create(['name' => 'Salon de la Danse 2027']);

    $this->actingAs($admin)
        ->post(route('admin.editions.store'), editionPayload($overrides))
        ->assertSessionHasErrors($field);
})->with([
    'nom déjà pris' => [['name' => 'Salon de la Danse 2027'], 'name'],
    'fin avant début' => [['end_date' => '2028-05-11'], 'end_date'],
    'max < min' => [['min_slots_per_volunteer' => 3, 'max_slots_per_volunteer' => 2], 'max_slots_per_volunteer'],
    'consécutifs à 0' => [['max_consecutive_slots' => 0], 'max_consecutive_slots'],
]);

test('a new edition can copy the grid of a previous one, shifted to its dates', function () {
    $admin = User::factory()->admin()->create();
    $source = Edition::factory()->create(['status' => 'active', 'start_date' => '2027-05-14', 'end_date' => '2027-05-16']);

    $friday = EventDay::factory()->for($source)->create(['date' => '2027-05-14', 'label' => 'Vendredi']);
    $sunday = EventDay::factory()->for($source)->create(['date' => '2027-05-16', 'label' => 'Dimanche']);
    $fridayMorning = TimeSlot::factory()->for($friday, 'eventDay')->create(['starts_at' => '08:30:00', 'ends_at' => '10:00:00', 'position' => 1]);
    $sundayMorning = TimeSlot::factory()->for($sunday, 'eventDay')->create(['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'position' => 1]);

    $cloakroom = Mission::factory()->for($source)->create(['name' => 'Vestiaires', 'description' => 'Niveau -1', 'default_capacity' => 4]);
    $cashDesk = Mission::factory()->restricted()->for($source)->create(['name' => 'Caisse', 'default_capacity' => 2]);
    $booked = MissionSlot::factory()->for($cloakroom, 'mission')->for($fridayMorning, 'timeSlot')->create(['capacity' => 6]);
    MissionSlot::factory()->for($cloakroom, 'mission')->for($sundayMorning, 'timeSlot')->create(['capacity' => 3]);
    MissionSlot::factory()->for($cashDesk, 'mission')->for($fridayMorning, 'timeSlot')->create(['capacity' => 2]);
    VolunteerAssignment::factory()->for(User::factory())->for($booked)->create(['time_slot_id' => $fridayMorning->id]);

    // 2028: starts on Friday 12 May but lasts two days only, so the shifted Sunday is left out.
    $this->actingAs($admin)
        ->post(route('admin.editions.store'), editionPayload(['end_date' => '2028-05-13', 'copy_from_edition_id' => $source->id]))
        ->assertSessionHas('status', fn (string $status) => str_contains($status, '1 jour(s), 1 créneau(x), 2 mission(s)'));

    $target = Edition::where('name', 'Salon de la Danse 2028')->firstOrFail();
    $days = $target->eventDays()->with('timeSlots')->get();

    expect($days)->toHaveCount(1)
        ->and($days->first()->date->format('Y-m-d'))->toBe('2028-05-12')
        ->and($days->first()->label)->toBe('Vendredi')
        ->and($days->first()->timeSlots->first()->only(['starts_at', 'ends_at', 'position']))
        ->toBe(['starts_at' => '08:30:00', 'ends_at' => '10:00:00', 'position' => 1]);

    $copiedCloakroom = $target->missions()->where('name', 'Vestiaires')->with('missionSlots')->firstOrFail();
    $copiedCashDesk = $target->missions()->where('name', 'Caisse')->with('missionSlots')->firstOrFail();

    expect($copiedCloakroom->description)->toBe('Niveau -1')
        ->and($copiedCloakroom->default_capacity)->toBe(4)
        ->and($copiedCloakroom->missionSlots->pluck('capacity')->all())->toBe([6])
        ->and($copiedCashDesk->is_public)->toBeFalse()
        ->and($copiedCashDesk->missionSlots->pluck('capacity')->all())->toBe([2])
        ->and($target->volunteers()->count())->toBe(0)
        ->and(VolunteerAssignment::whereIn('mission_slot_id', $copiedCloakroom->missionSlots->modelKeys())->exists())->toBeFalse();

    expect($source->eventDays()->count())->toBe(2)
        ->and($source->missions()->count())->toBe(2);
});

test('an admin can update an edition', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(editionPayload(['status' => 'draft']));

    $this->actingAs($admin)
        ->put(route('admin.editions.update', $edition), editionPayload(['name' => 'Salon 2028', 'max_slots_per_volunteer' => 4]))
        ->assertRedirect(route('admin.editions.index'));

    expect($edition->fresh()->only(['name', 'max_slots_per_volunteer']))->toBe(['name' => 'Salon 2028', 'max_slots_per_volunteer' => 4])
        ->and(AuditLog::where('action', 'edition.updated')->exists())->toBeTrue();
});

test('the dates of an edition cannot leave out days already created', function () {
    $admin = User::factory()->admin()->create();
    $edition = Edition::factory()->create(editionPayload());
    EventDay::factory()->for($edition)->create(['date' => '2028-05-14', 'label' => 'Dimanche']);

    $this->actingAs($admin)
        ->put(route('admin.editions.update', $edition), editionPayload(['end_date' => '2028-05-13']))
        ->assertSessionHasErrors('start_date');

    expect($edition->fresh()->end_date->format('Y-m-d'))->toBe('2028-05-14');
});

test('activating an edition archives the previous one and revokes its pending codes', function () {
    $admin = User::factory()->admin()->create();
    $previous = Edition::factory()->create(['status' => 'active']);
    $next = Edition::factory()->create(['status' => 'draft']);
    $pendingCode = InvitationCode::factory()->for($previous)->create(['status' => 'pending']);
    $usedCode = InvitationCode::factory()->for($previous)->create(['status' => 'used']);
    $nextCode = InvitationCode::factory()->for($next)->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->post(route('admin.editions.activate', $next))
        ->assertRedirect(route('admin.editions.index'))
        ->assertSessionHas('status', fn (string $status) => str_contains($status, "1 code(s) d'invitation en attente révoqué(s)"));

    expect(Edition::active()->id)->toBe($next->id)
        ->and($previous->fresh()->status)->toBe('archived')
        ->and($pendingCode->fresh()->status)->toBe('revoked')
        ->and($usedCode->fresh()->status)->toBe('used')
        ->and($nextCode->fresh()->status)->toBe('pending');

    $auditLog = AuditLog::where('action', 'edition.activated')->firstOrFail();
    expect($auditLog->changes['archived'])->toBe([$previous->name])
        ->and($auditLog->changes['revoked_invitation_codes'])->toBe(1);
});

test('an archived edition can be reactivated', function () {
    $admin = User::factory()->admin()->create();
    $archived = Edition::factory()->create(['status' => 'archived']);
    Edition::factory()->create(['status' => 'active']);

    $this->actingAs($admin)->post(route('admin.editions.activate', $archived));

    expect(Edition::active()->id)->toBe($archived->id)
        ->and(Edition::where('status', 'active')->count())->toBe(1);
});

test('activating the active edition does nothing', function () {
    $admin = User::factory()->admin()->create();
    $active = Edition::factory()->create(['status' => 'active']);

    $this->actingAs($admin)
        ->post(route('admin.editions.activate', $active))
        ->assertSessionHas('error');

    expect(AuditLog::where('action', 'edition.activated')->exists())->toBeFalse();
});
