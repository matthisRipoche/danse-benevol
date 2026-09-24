<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\EditionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'start_date',
    'end_date',
    'registration_opens_at',
    'registration_closes_at',
    'is_registration_locked',
    'min_slots_per_volunteer',
    'max_slots_per_volunteer',
    'max_consecutive_slots',
    'status',
])]
class Edition extends Model
{
    /** @use HasFactory<EditionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'is_registration_locked' => 'boolean',
            'min_slots_per_volunteer' => 'integer',
            'max_slots_per_volunteer' => 'integer',
            'max_consecutive_slots' => 'integer',
        ];
    }

    /**
     * @return HasMany<InvitationCode>
     */
    public function invitationCodes(): HasMany
    {
        return $this->hasMany(InvitationCode::class);
    }

    /**
     * @return HasMany<EventDay>
     */
    public function eventDays(): HasMany
    {
        return $this->hasMany(EventDay::class);
    }

    /**
     * @return HasMany<Mission>
     */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'edition_volunteers')
            ->using(EditionVolunteer::class)
            ->withPivot(['is_validated', 'validated_at', 'badge_uid'])
            ->withTimestamps();
    }

    /**
     * Where volunteers stand with the registration window: `locked` (suspended by an admin),
     * `not_open` (before the opening date), `closed` (after the closing date) or `open`.
     * A missing date leaves that side of the window open.
     */
    public function registrationStatus(): string
    {
        return match (true) {
            $this->is_registration_locked => 'locked',
            (bool) $this->registration_opens_at?->isFuture() => 'not_open',
            (bool) $this->registration_closes_at?->isPast() => 'closed',
            default => 'open',
        };
    }

    /**
     * Whether volunteers may currently book, cancel and validate their planning.
     */
    public function isRegistrationOpen(): bool
    {
        return $this->registrationStatus() === 'open';
    }

    /**
     * The explanation shown to volunteers while their planning is read-only, or null when it is open.
     */
    public function registrationClosedMessage(): ?string
    {
        $format = fn (CarbonInterface $date) => $date->copy()->timezone(config('app.display_timezone'))->format('d/m/Y à H:i');

        return match ($this->registrationStatus()) {
            'locked' => "Les inscriptions sont momentanément suspendues par l'organisation : ton planning est en lecture seule.",
            'not_open' => "Les inscriptions ouvriront le {$format($this->registration_opens_at)} : tu pourras alors réserver tes créneaux.",
            'closed' => "Les inscriptions sont closes depuis le {$format($this->registration_closes_at)} : ton planning est en lecture seule. Contacte l'équipe pour tout changement.",
            default => null,
        };
    }

    /**
     * The edition currently open for admin/volunteer operations.
     */
    public static function active(): self
    {
        return static::where('status', 'active')->firstOrFail();
    }
}
