<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TaxonomyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->word();

        return [
            'id' => fake()->uuid(),
            'parent_id' => null,
            'path' => null,
            'name' => $name,
            'slug' => Str::slug($name.'-'.fake()->unique()->numberBetween(1, 10000)),
            'metadata' => ['description' => fake()->sentence()],
        ];
    }
}
