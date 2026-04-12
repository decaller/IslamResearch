<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ScholarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'name' => fake()->name(),
            'metadata' => ['bio' => fake()->paragraph()],
        ];
    }
}
