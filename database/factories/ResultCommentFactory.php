<?php

namespace Database\Factories;

use App\Models\ResultComment;
use App\Models\Result;
use App\Models\SchoolAuthority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResultComment>
 */
class ResultCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teacherComments = [
            'Excellent performance. Keep it up!',
            'Good work. You can do better.',
            'Fair performance. More effort needed.',
            'Poor performance. Please see me.',
            'Outstanding! Well done.',
            'Satisfactory. Keep working hard.',
        ];

        $principalComments = [
            'Promoted to next class.',
            'Repeat class.',
            'Excellent overall performance.',
            'Good conduct and academic performance.',
            'Needs improvement in attitude and academics.',
        ];

        $type = fake()->randomElement(['teacher', 'principal', 'custom']);
        
        return [
            'result_id' => Result::factory(),
            'comment_authority_scope_id' => null,
            'comment_text' => $type === 'teacher' 
                ? fake()->randomElement($teacherComments)
                : fake()->randomElement($principalComments),
            'comment_type' => $type,
        ];
    }

    /**
     * Teacher comment
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment_type' => 'teacher',
            'comment_text' => fake()->randomElement([
                'Excellent performance. Keep it up!',
                'Good work. You can do better.',
                'Fair performance. More effort needed.',
                'Outstanding! Well done.',
            ]),
        ]);
    }

    /**
     * Principal comment
     */
    public function principal(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment_type' => 'principal',
            'comment_text' => fake()->randomElement([
                'Promoted to next class.',
                'Excellent overall performance.',
                'Good conduct and academic performance.',
            ]),
        ]);
    }
}
