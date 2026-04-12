<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LexiconRootFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'language' => fake()->randomElement(['ar', 'id']),
            'root_value' => fake()->word(),
            'embedding' => null,
            'metadata' => ['meaning' => fake()->word()],
        ];
    }
}
