<?php

namespace App\Models;

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
}
