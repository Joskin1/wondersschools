<?php

namespace Database\Factories;

use App\Models\Grading;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grading>
 */
class GradingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lowerBound = fake()->numberBetween(0, 90);
        $upperBound = $lowerBound + fake()->numberBetween(5, 10);

        return [
            'letter' => fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
            'lower_bound' => $lowerBound,
            'upper_bound' => min($upperBound, 100),
            'remark' => fake()->randomElement(['Excellent', 'Very Good', 'Good', 'Fair', 'Poor', 'Fail']),
            'subject_id' => null,
            'session' => null,
        ];
    }

    /**
     * Standard grading scheme (A-F)
     */
    public function standard(): static
    {
        return $this->sequence(
            ['letter' => 'A', 'lower_bound' => 70, 'upper_bound' => 100, 'remark' => 'Excellent'],
            ['letter' => 'B', 'lower_bound' => 60, 'upper_bound' => 69, 'remark' => 'Very Good'],
            ['letter' => 'C', 'lower_bound' => 50, 'upper_bound' => 59, 'remark' => 'Good'],
            ['letter' => 'D', 'lower_bound' => 45, 'upper_bound' => 49, 'remark' => 'Fair'],
            ['letter' => 'E', 'lower_bound' => 40, 'upper_bound' => 44, 'remark' => 'Pass'],
            ['letter' => 'F', 'lower_bound' => 0, 'upper_bound' => 39, 'remark' => 'Fail'],
        );
    }

    /**
     * For a specific subject
     */
    public function forSubject(int $subjectId): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_id' => $subjectId,
        ]);
    }

    /**
     * For a specific session
     */
    public function forSession(string $session): static
    {
        return $this->state(fn (array $attributes) => [
            'session' => $session,
        ]);
    }
}
