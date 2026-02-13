<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutExercise>
 */
class WorkoutExerciseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workout_session_id' => \App\Models\WorkoutSession::factory(),
            'exercise_id' => \App\Models\Exercise::factory(),
            'order' => fake()->numberBetween(1, 10),
            'target_sets' => fake()->numberBetween(3, 5),
            'target_reps_min' => 8,
            'target_reps_max' => 12,
            'target_rpe' => fake()->numberBetween(7, 9),
            'rest_seconds' => fake()->randomElement([60, 90, 120, 180]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
