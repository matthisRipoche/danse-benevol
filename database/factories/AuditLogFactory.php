<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->admin(),
            'action' => fake()->randomElement(['planning.override', 'invitation.create', 'password.reset', 'profile.validate_minor']),
            'subject_type' => 'User',
            'subject_id' => fake()->numberBetween(1, 1000),
            'changes' => ['before' => [], 'after' => []],
        ];
    }
}
