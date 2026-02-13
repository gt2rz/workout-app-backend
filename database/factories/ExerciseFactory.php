<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'instructions' => fake()->paragraph(),
            'video_url' => fake()->optional()->url(),
            'exercise_type_id' => \App\Models\ExerciseType::factory(),
            'is_custom' => false,
            'user_id' => null,
        ];
    }

    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_custom' => true,
            'user_id' => \App\Models\User::factory(),
        ]);
    }
}
