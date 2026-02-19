<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'date_of_birth' => fake()->date('Y-m-d', '-10 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'address' => fake()->address(),
            'previous_school' => fake()->company() . ' School',
            'parent_name' => fake()->name(),
            'parent_phone' => fake()->phoneNumber(),
            'parent_email' => fake()->safeEmail(),
        ];
    }
}
