<?php

namespace Database\Factories;

use App\Enums\ResourceType;
use App\Models\SourceBook;
use Illuminate\Database\Eloquent\Factories\Factory;

class SentenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'source_book_id' => SourceBook::factory(),
            'resource_type' => fake()->randomElement(array_column(ResourceType::cases(), 'value')),
            'sequence_number' => fake()->numberBetween(1, 1000),
            'sentence_text' => fake()->paragraph(),
            'metadata' => ['isnad' => fake()->name()],
            'embedding_ar' => null,
        ];
    }
}
