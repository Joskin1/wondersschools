<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'First Test',
                'Second Test',
                'Mid-Term Exam',
                'Final Exam',
                'Assignment',
                'Project',
            ]),
            'max_score' => $this->faker->numberBetween(10, 40),
            'is_active' => true,
        ];
    }
}
