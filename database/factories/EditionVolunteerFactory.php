<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\EditionVolunteer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditionVolunteer>
 */
class EditionVolunteerFactory extends Factory
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
            'edition_id' => Edition::factory(),
            'is_validated' => false,
            'validated_at' => null,
            'badge_uid' => null,
        ];
    }

    /**
     * Indicate that the volunteer's planning has been definitively validated.
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_validated' => true,
            'validated_at' => now(),
            'badge_uid' => fake()->uuid(),
        ]);
    }
}
