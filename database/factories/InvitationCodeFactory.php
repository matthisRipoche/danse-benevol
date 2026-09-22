<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvitationCode>
 */
class InvitationCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'code' => Str::upper(Str::random(8)),
            'email' => null,
            'status' => 'pending',
            'used_by_user_id' => null,
            'used_at' => null,
            'expires_at' => now()->addMonths(2),
            'created_by_id' => null,
        ];
    }

    /**
     * Indicate that the code has already been used to register.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'used',
            'used_by_user_id' => User::factory(),
            'used_at' => now(),
        ]);
    }
}
