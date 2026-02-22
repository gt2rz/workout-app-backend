<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SplitType>
 */
class SplitTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'days_per_week' => fake()->numberBetween(3, 6),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
