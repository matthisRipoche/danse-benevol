<?php

namespace App\Support;

use App\Models\Edition;
use App\Models\EventDay;
use App\Models\MissionSlot;
use Illuminate\Support\Facades\DB;

/**
 * Copies the days, time slots, missions and per-slot capacities of a past edition into a new one,
 * so each year's grid does not have to be typed again. Volunteers and bookings are never copied.
 */
class EditionScheduleCopier
{
    /**
     * Days are shifted by the gap between both start dates (same weekday layout); a shifted day
     * falling outside the target edition's dates is skipped, together with its capacities.
     *
     * @return array{days: int, timeSlots: int, missions: int}
     */
    public function copy(Edition $source, Edition $target): array
    {
        return DB::transaction(function () use ($source, $target) {
            $dayOffset = (int) $source->start_date->diffInDays($target->start_date, false);
            $timeSlotIdMap = [];
            $dayCount = 0;

            foreach ($source->eventDays()->with('timeSlots')->orderBy('date')->get() as $day) {
                $date = $day->date->copy()->addDays($dayOffset);

                if ($date->lt($target->start_date) || $date->gt($target->end_date)) {
                    continue;
                }

                $newDay = EventDay::create([
                    'edition_id' => $target->id,
                    'date' => $date,
                    'label' => EventDay::labelFor($date),
                ]);
                $dayCount++;

                foreach ($day->timeSlots as $timeSlot) {
                    $timeSlotIdMap[$timeSlot->id] = $newDay->timeSlots()->create([
                        'starts_at' => $timeSlot->starts_at,
                        'ends_at' => $timeSlot->ends_at,
                        'position' => $timeSlot->position,
                    ])->id;
                }
            }

            $missions = $source->missions()->with('missionSlots')->get();

            foreach ($missions as $mission) {
                $newMission = $mission->replicate();
                $newMission->edition_id = $target->id;
                $newMission->save();

                $mission->missionSlots
                    ->filter(fn (MissionSlot $missionSlot) => isset($timeSlotIdMap[$missionSlot->time_slot_id]))
                    ->each(fn (MissionSlot $missionSlot) => MissionSlot::create([
                        'mission_id' => $newMission->id,
                        'time_slot_id' => $timeSlotIdMap[$missionSlot->time_slot_id],
                        'capacity' => $missionSlot->capacity,
                    ]));
            }

            return [
                'days' => $dayCount,
                'timeSlots' => count($timeSlotIdMap),
                'missions' => $missions->count(),
            ];
        });
    }
}
