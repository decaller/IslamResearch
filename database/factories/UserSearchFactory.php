<?php

namespace Database\Factories;

use App\Models\Search;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSearchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'search_id' => Search::factory(),
            'filters' => ['scholars' => [fake()->uuid()]],
            'results_count' => fake()->numberBetween(0, 1000),
        ];
    }
}
