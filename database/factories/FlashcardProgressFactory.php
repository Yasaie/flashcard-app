<?php

namespace Database\Factories;

use App\Enums\FlashcardStatus;
use App\Models\Flashcard;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlashcardProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'flashcard_id' => Flashcard::factory(),
            'username' => $this->faker->firstName(),
            'status' => $this->faker->randomElement(FlashcardStatus::cases()),
        ];
    }
}
