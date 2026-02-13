<?php

namespace Database\Seeders;

use App\Models\ClassTeacherAssignment;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClassTeacherAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $activeSession = Session::active()->first();
        
        if (!$activeSession) {
            $this->command->warn('No active session found. Skipping class teacher assignments.');
            return;
        }

        $teachers = User::where('role', 'teacher')->get();
        $classrooms = Classroom::all();

        if ($teachers->isEmpty() || $classrooms->isEmpty()) {
            $this->command->warn('No teachers or classrooms found. Skipping class teacher assignments.');
            return;
        }

        // Assign one class per teacher (a teacher can only manage one class per session)
        $assignableCount = min($teachers->count(), $classrooms->count());

        foreach ($classrooms->take($assignableCount) as $index => $classroom) {
            $teacher = $teachers->get($index);

            ClassTeacherAssignment::updateOrCreate(
                [
                    'class_id' => $classroom->id,
                    'session_id' => $activeSession->id,
                ],
                [
                    'teacher_id' => $teacher->id,
                ]
            );

            $this->command->info("Assigned {$teacher->name} as class teacher for {$classroom->name}");
        }
    }
}
