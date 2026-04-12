<?php

namespace Database\Factories;

use App\Models\Sentence;
use Illuminate\Database\Eloquent\Factories\Factory;

class SentenceTransliterationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'sentence_id' => Sentence::factory(),
            'scheme' => fake()->randomElement(['ala_lc', 'buckwalter']),
            'transliteration_text' => fake()->paragraph(),
        ];
    }
}
