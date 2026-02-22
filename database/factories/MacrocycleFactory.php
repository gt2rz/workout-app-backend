<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Macrocycle>
 */
class MacrocycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-3 months', '+3 months');
        $durationWeeks = fake()->numberBetween(12, 32);

        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->words(3, true),
            'goal' => fake()->optional()->sentence(),
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->modify("+{$durationWeeks} weeks"),
            'duration_weeks' => $durationWeeks,
            'is_active' => false,
            'status' => 'planned',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'status' => 'active',
            'start_date' => now()->subWeeks(4),
            'end_date' => now()->addWeeks(20),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => 'completed',
            'start_date' => now()->subMonths(6),
            'end_date' => now()->subMonths(2),
        ]);
    }
}
