<?php

namespace Database\Factories;

use App\Models\LexiconRoot;
use Illuminate\Database\Eloquent\Factories\Factory;

class LexiconWordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'root_id' => LexiconRoot::factory(),
            'language' => fake()->randomElement(['ar', 'id']),
            'word_raw' => fake()->word(),
            'word_clean' => fake()->word(),
            'embedding_raw' => null,
            'embedding_clean' => null,
            'metadata' => ['frequency' => fake()->numberBetween(1, 100)],
        ];
    }
}
