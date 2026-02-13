<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutSession>
 */
class WorkoutSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'microcycle_id' => null,
            'workout_template_id' => null,
            'scheduled_date' => fake()->dateTimeBetween('-1 week', '+1 week'),
            'completed_at' => null,
            'duration_minutes' => fake()->numberBetween(30, 90),
            'overall_rpe' => null,
            'notes' => fake()->optional()->sentence(),
            'status' => 'scheduled',
        ];
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_date' => today(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
            'overall_rpe' => fake()->numberBetween(6, 10),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'completed_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }
}
