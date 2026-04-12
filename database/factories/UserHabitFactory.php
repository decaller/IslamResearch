<?php

namespace Database\Factories;

use App\Models\Sentence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserHabitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'sentence_id' => Sentence::factory(),
            'checked_at' => fake()->dateTime(),
        ];
    }
}
