<?php

namespace Database\Factories;

use App\Enums\ResourceType;
use App\Models\Scholar;
use Illuminate\Database\Eloquent\Factories\Factory;

class SourceBookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'title' => fake()->sentence(3),
            'scholar_id' => Scholar::factory(),
            'resource_type' => fake()->randomElement(array_column(ResourceType::cases(), 'value')),
            'language' => fake()->randomElement(['ar', 'id']),
            'status' => 'published',
            'metadata' => ['publication_year' => fake()->year()],
        ];
    }
}
