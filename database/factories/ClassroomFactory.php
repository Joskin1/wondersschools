<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $levels = ['JSS', 'SS'];
        $grades = [1, 2, 3];
        $sections = ['A', 'B', 'C'];

        $level = fake()->randomElement($levels);
        $grade = fake()->randomElement($grades);
        $section = fake()->randomElement($sections);

        return [
            'name' => "{$level} {$grade}{$section}",
            'level' => $level,
            'section' => $section,
        ];
    }
}
