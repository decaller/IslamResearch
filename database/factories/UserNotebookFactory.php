<?php

namespace Database\Factories;

use App\Models\UserNotebook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotebook>
 */
class UserNotebookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'content_md' => $this->faker->paragraphs(3, true),
            'is_published' => false,
            'view_mode' => 'article',
            'metadata' => [],
        ];
    }
}
