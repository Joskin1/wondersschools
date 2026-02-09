<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Term>
 */
class TermFactory extends Factory
{
    protected $model = Term::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $terms = [
            ['name' => 'First Term', 'order' => 1],
            ['name' => 'Second Term', 'order' => 2],
            ['name' => 'Third Term', 'order' => 3],
        ];

        $term = fake()->randomElement($terms);

        return [
            'session_id' => Session::factory(),
            'name' => $term['name'],
            'order' => $term['order'],
            'is_active' => false,
        ];
    }

    /**
     * Indicate that the term is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create a first term.
     */
    public function firstTerm(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'First Term',
            'order' => 1,
        ]);
    }

    /**
     * Create a second term.
     */
    public function secondTerm(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Second Term',
            'order' => 2,
        ]);
    }

    /**
     * Create a third term.
     */
    public function thirdTerm(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Third Term',
            'order' => 3,
        ]);
    }
}
