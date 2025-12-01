<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class TermFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['First Term', 'Second Term', 'Third Term']),
            'academic_session_id' => AcademicSession::factory(),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'is_current' => false,
        ];
    }
}
