<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SearchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'query' => fake()->unique()->sentence(),
            'embedding' => null,
        ];
    }
}
