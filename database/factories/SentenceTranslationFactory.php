<?php

namespace Database\Factories;

use App\Models\Scholar;
use App\Models\Sentence;
use Illuminate\Database\Eloquent\Factories\Factory;

class SentenceTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'sentence_id' => Sentence::factory(),
            'language' => fake()->randomElement(['id', 'en', 'ur', 'fr']),
            'scholar_id' => Scholar::factory(),
            'translation_text' => fake()->paragraph(),
            'embedding' => null,
        ];
    }
}
