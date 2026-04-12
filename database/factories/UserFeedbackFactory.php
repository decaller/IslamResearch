<?php

namespace Database\Factories;

use App\Enums\FeedbackStatus;
use App\Models\Sentence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'sentence_id' => Sentence::factory(),
            'search_query' => fake()->sentence(),
            'relevance_score' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(array_column(FeedbackStatus::cases(), 'value')),
            'reviewed_by' => null,
            'resolved_at' => null,
        ];
    }
}
