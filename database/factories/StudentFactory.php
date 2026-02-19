<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'status' => 'pending',
        ];
    }

    /**
     * Indicate that the student is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the student is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the student has a registration link.
     */
    public function withRegistrationLink(): static
    {
        return $this->state(function (array $attributes) {
            $rawToken = Student::generateRegistrationToken();
            
            return [
                'registration_slug' => Student::generateRegistrationSlug($attributes['full_name']),
                'registration_token' => Student::hashToken($rawToken),
                'registration_expires_at' => now()->addDays(3),
            ];
        });
    }

    /**
     * Indicate that the student has an expired registration link.
     */
    public function withExpiredRegistrationLink(): static
    {
        return $this->state(function (array $attributes) {
            $rawToken = Student::generateRegistrationToken();
            
            return [
                'registration_slug' => Student::generateRegistrationSlug($attributes['full_name']),
                'registration_token' => Student::hashToken($rawToken),
                'registration_expires_at' => now()->subDay(),
            ];
        });
    }
}
