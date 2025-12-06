<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Result>
 */
class ResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => \App\Models\Student::factory(),
            'academic_session_id' => \App\Models\AcademicSession::factory(),
            'term_id' => \App\Models\Term::factory(),
            'classroom_id' => \App\Models\Classroom::factory(),
            'total_score' => $this->faker->randomFloat(2, 0, 100),
            'average_score' => $this->faker->randomFloat(2, 0, 100),
            'position' => $this->faker->numberBetween(1, 30),
            'grade' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
            'teacher_remark' => $this->faker->sentence(),
            'principal_remark' => $this->faker->sentence(),
        ];
    }
}
