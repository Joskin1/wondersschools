<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Score>
 */
class ScoreFactory extends Factory
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
            'subject_id' => \App\Models\Subject::factory(),
            'academic_session_id' => \App\Models\AcademicSession::factory(),
            'term_id' => \App\Models\Term::factory(),
            'ca_score' => $this->faker->numberBetween(0, 40),
            'exam_score' => $this->faker->numberBetween(0, 60),
        ];
    }
}
