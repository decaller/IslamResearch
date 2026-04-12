<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserWorkspaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'is_active' => fake()->boolean(),
            'layout_state' => ['sidebar' => 'open'],
        ];
    }
}
