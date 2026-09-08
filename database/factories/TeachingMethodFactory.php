<?php

namespace Database\Factories;

use App\Models\TeachingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeachingMethodFactory extends Factory
{
    protected $model = TeachingMethod::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Discussion Method',
                'Demonstration Method',
                'Question and Answer',
                'Group Work',
                'Practical Method',
                'Lecture Method',
                'Project-Based Learning',
            ]) . ' ' . $this->faker->unique()->randomNumber(3),
        ];
    }
}
