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
        $missionSlot = MissionSlot::factory()->create();

        return [
            'user_id' => User::factory(),
            'mission_slot_id' => $missionSlot->id,
            'time_slot_id' => $missionSlot->time_slot_id,
            'status' => 'draft',
            'assigned_by_id' => null,
        ];
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
