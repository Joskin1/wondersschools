<?php

namespace Database\Factories;

use App\Models\SchoolAuthority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolAuthority>
 */
class SchoolAuthorityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'title' => fake()->randomElement(['Mr.', 'Mrs.', 'Dr.', 'Prof.']),
            'signature_path' => null,
            'signature_top' => fake()->numberBetween(100, 500),
            'signature_left' => fake()->numberBetween(100, 500),
            'comment_top' => fake()->numberBetween(100, 500),
            'comment_left' => fake()->numberBetween(100, 500),
            'display_order' => fake()->numberBetween(0, 10),
            'school_id' => null,
        ];
    }

    /**
     * Principal authority
     */
    public function principal(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => fake()->randomElement(['Mr.', 'Mrs.', 'Dr.']),
            'display_order' => 1,
        ]);
    }

    /**
     * Vice Principal authority
     */
    public function vicePrincipal(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => fake()->randomElement(['Mr.', 'Mrs.']),
            'display_order' => 2,
        ]);
    }

    /**
     * Class Teacher authority
     */
    public function classTeacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => fake()->randomElement(['Mr.', 'Mrs.', 'Miss']),
            'display_order' => 3,
        ]);
    }
}
