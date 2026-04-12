<?php

namespace Database\Factories;

use App\Models\SourceBook;
use Illuminate\Database\Eloquent\Factories\Factory;

class SentenceJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'source_book_id' => SourceBook::factory(),
            'raw_text' => fake()->paragraph(),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'attempts' => fake()->numberBetween(0, 3),
            'error_log' => null,
            'completed_at' => null,
        ];
    }
}
