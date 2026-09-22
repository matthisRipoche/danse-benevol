<?php

namespace Database\Factories;

use App\Models\Edition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Edition>
 */
class EditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2026, 2030);
        $name = "Salon de la Danse {$year}";
        $startDate = fake()->dateTimeBetween("first friday of may {$year}", "second friday of may {$year}");

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->modify('+2 days'),
            'registration_opens_at' => now()->subMonths(3),
            'registration_closes_at' => now()->subMonth(),
            'is_registration_locked' => false,
            'min_slots_per_volunteer' => 1,
            'max_slots_per_volunteer' => 3,
            'max_consecutive_slots' => 2,
            'status' => 'draft',
        ];
    }
}
