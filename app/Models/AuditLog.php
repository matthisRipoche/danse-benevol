<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['admin_id', 'action', 'subject_type', 'subject_id', 'changes'])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory, MassPrunable;

    /**
     * How long entries are kept before `model:prune` deletes them.
     */
    public const int RETENTION_MONTHS = 12;

    /**
     * The model does not have an `updated_at` column: a log entry is never modified.
     */
    const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * An entry is written once and never changed: updates and deletions through the model are refused.
     * Only the scheduled retention purge (a mass query, see prunable()) removes old entries.
     */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException("Une entrée du journal d'audit ne peut pas être modifiée."));
        static::deleting(fn () => throw new LogicException("Une entrée du journal d'audit ne peut pas être supprimée."));
    }

    /**
     * Entries older than the retention period, deleted by the scheduled `model:prune` command.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subMonths(self::RETENTION_MONTHS));
    }

    /**
     * @return BelongsTo<User>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Record an admin action against a subject model.
     *
     * @param  array<string, mixed>  $changes
     */
    public static function record(User $admin, string $action, Model $subject, array $changes = []): self
    {
        return static::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->id,
            'changes' => $changes,
        ]);
    }
}
