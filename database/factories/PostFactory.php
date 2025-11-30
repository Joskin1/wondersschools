<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();
        return [
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'body' => fake()->paragraphs(3, true),
            'image' => 'https://placehold.co/600x400', // Placeholder image
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'is_featured' => fake()->boolean(20),
        ];
    }
}
