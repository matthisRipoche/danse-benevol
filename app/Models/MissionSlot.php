<?php

namespace App\Models;

use Database\Factories\MissionSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['mission_id', 'time_slot_id', 'capacity'])]
class MissionSlot extends Model
{
    /** @use HasFactory<MissionSlotFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Mission>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * @return BelongsTo<TimeSlot>
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * @return HasMany<VolunteerAssignment>
     */
    public function volunteerAssignments(): HasMany
    {
        return $this->hasMany(VolunteerAssignment::class);
    }

    /**
     * Number of assignments still remaining before the capacity is reached.
     */
    public function remainingCapacity(): int
    {
        return max(0, $this->capacity - $this->volunteerAssignments()->count());
    }

    /**
     * The gauge status used to color-code availability: 'disponible', 'presque-complet' or 'complet'.
     */
    public function gaugeStatus(): string
    {
        $remaining = $this->remainingCapacity();

        if ($remaining === 0) {
            return 'complet';
        }

        return $remaining / $this->capacity > 0.5 ? 'disponible' : 'presque-complet';
    }
}
