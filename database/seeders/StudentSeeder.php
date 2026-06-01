<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Session;
use App\Models\Classroom;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /** Number of students to create */
    private const COUNT = 1000;

    public function run(): void
    {
        $activeSession = Session::active()->first();
        if (! $activeSession) {
            $this->command->error('No active session found – aborting student seeding.');
            return;
        }

        $classroomIds = Classroom::pluck('id')->toArray();
        if (empty($classroomIds)) {
            $this->command->error('No classrooms found – aborting student seeding.');
            return;
        }

        for ($i = 1; $i <= self::COUNT; $i++) {
            // Create user account
            $user = User::firstOrCreate(
                ['email' => "student{$i}@wonders.test"],
                [
                    'name' => "Student {$i}",
                    'password' => bcrypt('password'),
                    'role' => 'student',
                    'is_active' => true,
                    'registration_completed_at' => now(),
                ]
            );

            // Create linked student record
            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $user->name,
                    'status' => 'active',
                    'registration_completed_at' => now(),
                    'is_portal_active' => true,
                    'activated_at' => now(),
                ]
            );

            // Enroll student in a random classroom for the active session
            StudentEnrollment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'session_id' => $activeSession->id,
                ],
                [
                    'classroom_id' => $classroomIds[array_rand($classroomIds)],
                ]
            );
        }

        $this->command->info('👨‍🎓 1 000 students seeded (password: password) and enrolled.');
    }
}
