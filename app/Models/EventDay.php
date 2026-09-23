<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\EventDayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['edition_id', 'date', 'label'])]
class EventDay extends Model
{
    /** @use HasFactory<EventDayFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Edition>
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * @return HasMany<TimeSlot>
     */
    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    /**
     * French weekday name of a date, used as the day's label.
     */
    public static function labelFor(CarbonInterface $date): string
    {
        return ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'][$date->dayOfWeekIso - 1];
    }

    /**
     * Renumber the day's time slots 1, 2, 3… in chronological order: the consecutive-slots
     * rule of the planning relies on these positions.
     */
    public function renumberTimeSlots(): void
    {
        $timeSlots = $this->timeSlots()->orderBy('starts_at')->get();

        // Two passes so that the unique (event_day_id, position) index never collides midway.
        $timeSlots->each(fn (TimeSlot $timeSlot, int $index) => $timeSlot->update(['position' => 100 + $index]));
        $timeSlots->each(fn (TimeSlot $timeSlot, int $index) => $timeSlot->update(['position' => $index + 1]));
    }
}
