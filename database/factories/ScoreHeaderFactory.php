<?php

namespace Database\Factories;

use App\Models\ScoreHeader;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScoreHeader>
 */
class ScoreHeaderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['CA1', 'CA2', 'CA3', 'Exam', 'Project']),
            'max_score' => fake()->randomElement([10, 20, 30, 40, 60]),
            'school_class_id' => Classroom::factory(),
            'session' => '2023/2024',
            'term' => fake()->numberBetween(1, 3),
            'display_order' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Standard score headers (CA1: 20, CA2: 20, Exam: 60)
     */
    public function standard(int $classroomId, string $session, int $term): static
    {
        return $this->sequence(
            [
                'name' => 'CA1',
                'max_score' => 20,
                'school_class_id' => $classroomId,
                'session' => $session,
                'term' => $term,
                'display_order' => 1,
            ],
            [
                'name' => 'CA2',
                'max_score' => 20,
                'school_class_id' => $classroomId,
                'session' => $session,
                'term' => $term,
                'display_order' => 2,
            ],
            [
                'name' => 'Exam',
                'max_score' => 60,
                'school_class_id' => $classroomId,
                'session' => $session,
                'term' => $term,
                'display_order' => 3,
            ],
        );
    }

    /**
     * For a specific classroom
     */
    public function forClassroom(int $classroomId, string $session, int $term): static
    {
        return $this->state(fn (array $attributes) => [
            'school_class_id' => $classroomId,
            'session' => $session,
            'term' => $term,
        ]);
    }
}
