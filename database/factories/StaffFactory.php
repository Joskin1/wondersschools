<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'surname' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'other_name' => fake()->optional(0.3)->firstName(),
            'role' => fake()->jobTitle,
            'bio' => fake()->paragraph,
            'image' => null,
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}
