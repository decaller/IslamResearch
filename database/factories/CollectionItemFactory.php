<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\Sentence;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'collection_id' => Collection::factory(),
            'itemable_type' => Sentence::class,
            'itemable_id' => Sentence::factory(),
            'raw_query' => null,
            'metadata' => null,
        ];
    }
}
