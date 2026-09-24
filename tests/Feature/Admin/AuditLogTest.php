<?php

use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function auditEntryBy(User $admin): AuditLog
{
    return AuditLog::record($admin, 'mission.created', Edition::factory()->create(), ['name' => 'Vestiaires']);
}

test('an audit entry cannot be modified', function () {
    $entry = auditEntryBy(User::factory()->admin()->create());

    expect(fn () => $entry->update(['action' => 'rien.fait']))->toThrow(LogicException::class);
    expect($entry->fresh()->action)->toBe('mission.created');
});

test('an audit entry cannot be deleted', function () {
    $entry = auditEntryBy(User::factory()->admin()->create());

    expect(fn () => $entry->delete())->toThrow(LogicException::class);
    expect(AuditLog::whereKey($entry->id)->exists())->toBeTrue();
});

test('deleting an admin account does not erase their audit trail', function () {
    $admin = User::factory()->admin()->create();
    $entry = auditEntryBy($admin);

    expect(fn () => $admin->delete())->toThrow(QueryException::class);
    expect(AuditLog::whereKey($entry->id)->exists())->toBeTrue();
});

test('entries are purged after 12 months, not before', function () {
    $admin = User::factory()->admin()->create();

    $this->travelTo(now()->subMonths(12)->subDay());
    $expiredEntry = auditEntryBy($admin);
    $this->travelBack();

    $this->travelTo(now()->subMonths(11));
    $recentEntry = auditEntryBy($admin);
    $this->travelBack();

    $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

    expect(AuditLog::whereKey($expiredEntry->id)->exists())->toBeFalse()
        ->and(AuditLog::whereKey($recentEntry->id)->exists())->toBeTrue();
});

test('the purge is scheduled every day', function () {
    $pruneEvent = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains($event->command, 'model:prune'));

    expect($pruneEvent)->not->toBeNull()
        ->and($pruneEvent->expression)->toBe('0 0 * * *');
});

test('there is no route to edit or delete audit entries', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'audit') || str_contains($route->uri(), 'journal'));

    expect($routes)->toBeEmpty();
});
