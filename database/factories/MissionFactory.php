<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mission>
 */
class MissionFactory extends Factory
{
    /**
     * Missions ouvertes à la réservation publique, telles que listées au cahier des charges.
     *
     * @var array<int, string>
     */
    protected const PUBLIC_MISSIONS = [
        'Accueil exposants',
        'Vestiaires',
        'Point Info',
        'Masterclass/Conférences',
        'Loges danseurs',
        'Logistique (Niveau 0)',
        'Logistique (Niveau -2)',
        'Scène principale',
        'Stand JayDance',
        'Village Danses du Monde',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'name' => fake()->randomElement(self::PUBLIC_MISSIONS),
            'description' => fake()->sentence(),
            'is_public' => true,
            'is_adult_only' => false,
            'default_capacity' => 5,
        ];
    }

    /**
     * Indicate that the mission is a sensitive, admin-only assigned post (Billetterie, Caisse).
     */
    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['Billetterie', 'Caisse']),
            'is_public' => false,
        ]);
    }

    /**
     * Indicate that minor volunteers can neither book nor be assigned to the mission.
     */
    public function adultOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_adult_only' => true,
        ]);
    }
}
