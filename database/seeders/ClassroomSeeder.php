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
        $classrooms = [
            ['name' => 'JSS1', 'class_order' => 1, 'is_active' => true],
            ['name' => 'JSS2', 'class_order' => 2, 'is_active' => true],
            ['name' => 'JSS3', 'class_order' => 3, 'is_active' => true],
            ['name' => 'SS1',  'class_order' => 4, 'is_active' => true],
            ['name' => 'SS2',  'class_order' => 5, 'is_active' => true],
            ['name' => 'SS3',  'class_order' => 6, 'is_active' => true],
        ];

        foreach ($classrooms as $classroom) {
            Classroom::firstOrCreate(
                ['name' => $classroom['name']],
                $classroom
            );
        }

        $this->command->info('Classrooms seeded successfully!');
    }
}
