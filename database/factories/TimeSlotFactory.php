<?php

namespace Database\Factories;

use App\Models\EventDay;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    /**
     * The 5 two-hour slots defined by the cahier des charges.
     *
     * @var array<int, array{starts_at: string, ends_at: string}>
     */
    protected const SLOTS = [
        1 => ['starts_at' => '08:30:00', 'ends_at' => '10:00:00'],
        2 => ['starts_at' => '10:00:00', 'ends_at' => '12:00:00'],
        3 => ['starts_at' => '12:00:00', 'ends_at' => '14:00:00'],
        4 => ['starts_at' => '14:00:00', 'ends_at' => '16:00:00'],
        5 => ['starts_at' => '16:00:00', 'ends_at' => '18:00:00'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $position = fake()->numberBetween(1, 5);

        return [
            'event_day_id' => EventDay::factory(),
            'starts_at' => self::SLOTS[$position]['starts_at'],
            'ends_at' => self::SLOTS[$position]['ends_at'],
            'position' => $position,
        ];
    }
}
