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
            'classroom_id' => \App\Models\Classroom::factory(),
            'score_header_id' => \App\Models\ScoreHeader::factory(),
            'session' => '2024/2025',
            'term' => 1,
            'value' => $this->faker->numberBetween(0, 100),
        ];
    }
}
