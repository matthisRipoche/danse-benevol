<?php

namespace Database\Factories;

use App\Models\MissionSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolunteerAssignment>
 */
class VolunteerAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mission_slot_id' => MissionSlot::factory(),
            'status' => 'draft',
            'assigned_by_id' => null,
        ];
    }

    /**
     * Keep `time_slot_id` in sync with the (possibly overridden) mission slot, since it is
     * denormalized purely to support the DB-level uniqueness constraint.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (VolunteerAssignment $assignment) {
            $assignment->time_slot_id ??= MissionSlot::find($assignment->mission_slot_id)?->time_slot_id;
        });
    }

    /**
     * Indicate that the assignment has been validated by the volunteer.
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'validated',
        ]);
    }
}
