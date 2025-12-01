<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_session_id' => AcademicSession::factory(),
            'term_id' => Term::factory(),
            'classroom_id' => Classroom::factory(),
            'total_score' => $this->faker->numberBetween(0, 300),
            'average_score' => $this->faker->numberBetween(0, 100),
            'position' => $this->faker->numberBetween(1, 50),
            'grade' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
            'teacher_remark' => $this->faker->sentence(),
            'principal_remark' => $this->faker->sentence(),
        ];
    }
}
