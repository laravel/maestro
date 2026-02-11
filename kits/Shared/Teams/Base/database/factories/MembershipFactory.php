<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = config('teams.user_model');

        return [
            'team_id' => Team::factory(),
            'user_id' => $userModel::factory(),
            'role' => TeamRole::Member,
        ];
    }

    /**
     * Indicate that the member is an owner.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TeamRole::Owner,
        ]);
    }

    /**
     * Indicate that the member is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TeamRole::Admin,
        ]);
    }
}
