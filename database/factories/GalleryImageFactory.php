<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => 'https://placehold.co/600x400', // Placeholder image
            'category' => $this->faker->randomElement(['Sports', 'Graduation', 'Classroom', 'Events']),
            'caption' => $this->faker->sentence,
        ];
    }
}
