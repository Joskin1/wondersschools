<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                '2023/2024',
                '2024/2025',
                '2025/2026',
                '2026/2027',
            ]),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'is_current' => false,
        ];
    }
}
