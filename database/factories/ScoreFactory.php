<?php

namespace Database\Factories;

use App\Models\AssessmentType;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'assessment_type_id' => AssessmentType::factory(),
            'score' => $this->faker->numberBetween(0, 100),
        ];
    }
}
