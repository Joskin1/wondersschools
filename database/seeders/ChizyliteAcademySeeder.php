<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Session;
use Illuminate\Database\Seeder;

class ChizyliteAcademySeeder extends Seeder
{
    /**
     * Run the full academy seeding process.
     */
    public function run(): void
    {
        // Ensure prerequisite data exists
        $this->call([
            ClassroomSeeder::class,
            SubjectSeeder::class,
            SessionSeeder::class, // creates an active session & term
        ]);

        // Seed teachers, students and their relationships
        $this->call([
            TeacherSeeder::class,
            StudentSeeder::class,
            TeacherSubjectAssignmentSeeder::class,
        ]);

        $this->command->info('✅ Chizylite Academy fully seeded.');
    }
}
