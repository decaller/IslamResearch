<?php

namespace Database\Factories;

use App\Enums\ActionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserJourneyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'action_type' => fake()->randomElement(array_column(ActionType::cases(), 'value')),
            'target_sentence_id' => null,
            'target_search_id' => null,
            'context_data' => ['scroll_y' => 500],
        ];
    }
}
