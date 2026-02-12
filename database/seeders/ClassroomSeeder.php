<?php

namespace Database\Seeders;

use App\Models\Classroom;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classrooms = [];

        // JSS (Junior Secondary School) 1-3
        foreach (['JSS 1', 'JSS 2', 'JSS 3'] as $level) {
            foreach (['A', 'B', 'C'] as $section) {
                $classrooms[] = [
                    'name' => "{$level}{$section}",
                    'level' => $level,
                    'section' => $section,
                ];
            }
        }

        // SS (Senior Secondary) 1-3
        foreach (['SS 1', 'SS 2', 'SS 3'] as $level) {
            foreach (['A', 'B', 'C'] as $section) {
                $classrooms[] = [
                    'name' => "{$level}{$section}",
                    'level' => $level,
                    'section' => $section,
                ];
            }
        }

        foreach ($classrooms as $classroom) {
            Classroom::firstOrCreate(
                ['name' => $classroom['name']],
                $classroom
            );
        }

        $this->command->info('Classrooms seeded successfully!');
    }
}
