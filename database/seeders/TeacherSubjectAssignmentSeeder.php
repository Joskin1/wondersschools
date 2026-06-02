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
    public function run(): void
    {
        $activeSession = Session::active()->first();
        if (! $activeSession || ! $activeSession->activeTerm) {
            $this->command->error('Active session/term missing – run SessionSeeder first.');
            return;
        }
        $activeTerm = $activeSession->activeTerm;

        $teachers = User::where('role', 'teacher')->get();
        $subjects = Subject::all();
        $classrooms = Classroom::all();

        if ($subjects->isEmpty() || $classrooms->isEmpty()) {
            $this->command->error('Subjects or classrooms missing – run their seeders first.');
            return;
        }

        $assignmentCount = 0;
        foreach ($teachers as $teacher) {
            // Each teacher gets 3‑5 random subjects
            $teacherSubjects = $subjects->random(min(5, $subjects->count()));
            foreach ($teacherSubjects as $subject) {
                // For each subject, assign 2‑3 random classrooms
                $teacherClassrooms = $classrooms->random(min(3, $classrooms->count()))->all();
                foreach ($teacherClassrooms as $classroom) {
                    $exists = TeacherSubjectAssignment::where([
                        'subject_id'   => $subject->id,
                        'classroom_id' => $classroom->id,
                        'session_id'   => $activeSession->id,
                        'term_id'      => $activeTerm->id,
                    ])->exists();
                    if (! $exists) {
                        TeacherSubjectAssignment::create([
                            'teacher_id'   => $teacher->id,
                            'subject_id'   => $subject->id,
                            'classroom_id' => $classroom->id,
                            'session_id'   => $activeSession->id,
                            'term_id'      => $activeTerm->id,
                        ]);
                        // Associate the subject with the classroom in the pivot table
                        $classroom->subjects()->syncWithoutDetaching([$subject->id]);
                        $assignmentCount++;
                    }
                }
            }
        }

        $this->command->info("📚 Created {$assignmentCount} teacher‑subject‑classroom assignments.");
    }
}
