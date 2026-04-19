<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entity>
 */
class EntityFactory extends Factory
{
    protected $model = Entity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'canonical_name' => $this->faker->unique()->word(),
            'entity_type' => $this->faker->randomElement(EntityType::cases()),
            'aliases' => [],
            'description' => $this->faker->sentence(),
            'wikipedia_url' => $this->faker->url(),
            'metadata' => [],
        ];
    }
}
