<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Microcycle>
 */
class MicrocycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-2 months', '+2 months');

        return [
            'mesocycle_id' => \App\Models\Mesocycle::factory(),
            'week_number' => fake()->numberBetween(1, 8),
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->modify('+6 days'),
            'is_deload' => false,
            'target_volume_percentage' => 100,
            'actual_volume_completed' => null,
            'status' => 'planned',
        ];
    }

    public function deload(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_deload' => true,
            'target_volume_percentage' => 60,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => now()->startOfWeek(),
            'end_date' => now()->endOfWeek(),
        ]);
    }
}
