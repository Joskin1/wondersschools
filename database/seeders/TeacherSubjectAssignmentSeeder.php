<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Database\Seeder;

class TeacherSubjectAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active session and term
        $activeSession = Session::active()->first();
        
        if (!$activeSession || !$activeSession->activeTerm) {
            $this->command->error('No active session or term found. Please run SessionSeeder first.');
            return;
        }

        $activeTerm = $activeSession->activeTerm;

        // Get or create teacher users
        $teachers = [];
        for ($i = 1; $i <= 5; $i++) {
            $teachers[] = User::firstOrCreate(
                ['email' => "teacher{$i}@wondersschools.com"],
                [
                    'name' => "Teacher {$i}",
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                ]
            );
        }

        // Get subjects and classrooms
        $subjects = Subject::limit(10)->get();
        $classrooms = Classroom::limit(6)->get();

        if ($subjects->isEmpty() || $classrooms->isEmpty()) {
            $this->command->error('No subjects or classrooms found. Please run SubjectSeeder and ClassroomSeeder first.');
            return;
        }

        // Assign each teacher to 2-3 subjects and 2-3 classrooms
        $assignmentCount = 0;
        foreach ($teachers as $teacher) {
            $teacherSubjects = $subjects->random(min(3, $subjects->count()));
            $teacherClassrooms = $classrooms->random(min(3, $classrooms->count()));

            foreach ($teacherSubjects as $subject) {
                foreach ($teacherClassrooms as $classroom) {
                    TeacherSubjectAssignment::firstOrCreate([
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'classroom_id' => $classroom->id,
                        'session_id' => $activeSession->id,
                        'term_id' => $activeTerm->id,
                    ]);
                    $assignmentCount++;
                }
            }
        }

        $this->command->info("Created {$assignmentCount} teacher-subject-classroom assignments!");
    }
}
