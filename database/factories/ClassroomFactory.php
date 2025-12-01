<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Reception',
                'Year 1',
                'Year 2',
                'Year 3',
                'Year 4',
                'Year 5',
                'Year 6',
            ]),
            'staff_id' => null,
        ];
    }
}
