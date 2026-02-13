<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = [
            'Mathematics',
            'English Language',
            'Physics',
            'Chemistry',
            'Biology',
            'Economics',
            'Government',
            'Literature',
            'Geography',
            'History',
            'Computer Science',
            'Agricultural Science',
            'Civic Education',
            'Christian Religious Studies',
            'Islamic Religious Studies',
            'French',
            'Yoruba',
            'Igbo',
            'Hausa',
        ];

        return [
            'name' => fake()->unique()->randomElement($subjects),
            'code' => strtoupper(fake()->unique()->lexify('???')),
        ];
    }
}
