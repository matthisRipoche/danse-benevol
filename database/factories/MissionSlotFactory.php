<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionSlot>
 */
class MissionSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(),
            'time_slot_id' => TimeSlot::factory(),
            'capacity' => fake()->numberBetween(2, 10),
        ];
    }
}
