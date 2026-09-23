<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TimeSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['event_day_id', 'starts_at', 'ends_at', 'position'])]
class TimeSlot extends Model
{
    /** @use HasFactory<TimeSlotFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<EventDay>
     */
    public function eventDay(): BelongsTo
    {
        return $this->belongsTo(EventDay::class);
    }

    /**
     * @return HasMany<MissionSlot>
     */
    public function missionSlots(): HasMany
    {
        return $this->hasMany(MissionSlot::class);
    }

    /**
     * @return HasMany<VolunteerAssignment>
     */
    public function volunteerAssignments(): HasMany
    {
        return $this->hasMany(VolunteerAssignment::class);
    }

    /**
     * Length of the time slot, in minutes.
     */
    public function durationInMinutes(): int
    {
        return (int) Carbon::parse($this->starts_at)->diffInMinutes(Carbon::parse($this->ends_at));
    }
}
