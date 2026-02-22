<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mesocycle>
 */
class MesocycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'macrocycle_id' => \App\Models\Macrocycle::factory(),
            'mesocycle_type_id' => \App\Models\MesocycleType::factory(),
            'split_type_id' => \App\Models\SplitType::factory(),
            'order' => fake()->numberBetween(1, 6),
            'start_week' => fake()->numberBetween(1, 20),
            'duration_weeks' => fake()->numberBetween(4, 8),
            'deload_weeks' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
