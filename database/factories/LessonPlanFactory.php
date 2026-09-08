<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\LessonPlan;
use App\Models\Session;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonPlanFactory extends Factory
{
    protected $model = LessonPlan::class;

    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->state(['role' => 'teacher']),
            'subject_id' => Subject::factory(),
            'classroom_id' => Classroom::factory(),
            'session_id' => Session::factory(),
            'term_id' => Term::factory(),
            'week_number' => $this->faker->numberBetween(1, config('academic.weeks_per_term')),
            'status' => 'pending',
            'title' => $this->faker->sentence(4),
            'time' => '40 mins',
            'section' => 'A',
            'learning_objectives' => [
                'Identify key definitions and principles',
                'Explain processes with appropriate examples',
                'Demonstrate understanding via practice exercises',
            ],
            'key_vocabulary' => 'Photosynthesis, Chlorophyll, Stomata, Glucose',
            'prior_knowledge' => 'Students have previously learned about parts of a plant in Basic Science.',
            'content' => '<p>Detailed explanation of photosynthesis and cellular respiration in green plants.</p>',
            'presentation_steps' => [
                'Teacher introduces topic by displaying real plant leaves.',
                'Teacher explains the role of sunlight and chlorophyll in food production.',
                'Students observe leaf cross-section under microscope and record observations.',
            ],
            'strategies_activities' => 'Interactive discussion, group diagram sketching, and short class Q&A.',
            'evaluation_questions' => [
                'What is photosynthesis?',
                'State two essential conditions for photosynthesis to take place.',
                'Name the green pigment in plant leaves.',
            ],
            'conclusion' => 'Teacher summarizes the main points and answers student questions.',
            'assignment' => 'Draw and label a plant cell and state the function of chloroplasts.',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'admin_comment' => 'Please revise presentation steps and evaluation questions.',
            'reviewed_at' => now(),
        ]);
    }
}
