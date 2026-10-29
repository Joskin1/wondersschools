<?php

namespace Database\Seeders;

use App\Models\ScoreHead;
use Illuminate\Database\Seeder;

class ScoreHeadSeeder extends Seeder
{
    /**
     * Seed default score heads.
     *
     * Idempotent — firstOrCreate never overwrites admin customisations.
     * Default structure: Classwork (10) + Test 1 (10) + Exam (80) = 100.
     */
    public function run(): void
    {
        $defaults = [
            ['name' => 'Classwork',     'max_score' => 10, 'type' => 'midterm'],
            ['name' => 'Assignment',    'max_score' => 10, 'type' => 'midterm'],
            ['name' => 'Mid-Term Test', 'max_score' => 20, 'type' => 'midterm'],
            ['name' => 'Exam',          'max_score' => 60, 'type' => 'exam'],
        ];

        foreach ($defaults as $item) {
            ScoreHead::firstOrCreate(
                ['name' => $item['name']],
                [
                    'max_score' => $item['max_score'],
                    'type'      => $item['type'] ?? 'midterm',
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Score heads seeded: Classwork, Assignment, Mid-Term Test, Exam.');
    }
}
