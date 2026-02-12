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

        // Assign class teachers to some classrooms
        $assignedClassrooms = $classrooms->take(min(5, $classrooms->count()));

        foreach ($assignedClassrooms as $index => $classroom) {
            $teacher = $teachers->get($index % $teachers->count());

            ClassTeacherAssignment::create([
                'teacher_id' => $teacher->id,
                'class_id' => $classroom->id,
                'session_id' => $activeSession->id,
            ]);

            $this->command->info("Assigned {$teacher->name} as class teacher for {$classroom->name}");
        }
    }
}
