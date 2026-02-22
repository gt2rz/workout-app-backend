<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MesocycleType>
 */
class MesocycleTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'rep_range_min' => fake()->numberBetween(5, 10),
            'rep_range_max' => fake()->numberBetween(12, 20),
            'typical_duration_weeks' => fake()->numberBetween(4, 8),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
