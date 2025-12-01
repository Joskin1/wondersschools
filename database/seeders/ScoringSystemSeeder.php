<?php

namespace Database\Seeders;

use App\Models\AssessmentType;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class ScoringSystemSeeder extends Seeder
{
    public function run(): void
    {
        // Create classrooms
        $classrooms = [
            ['name' => 'Reception'],
            ['name' => 'Year 1'],
            ['name' => 'Year 2'],
            ['name' => 'Year 3'],
            ['name' => 'Year 4'],
            ['name' => 'Year 5'],
            ['name' => 'Year 6'],
        ];

        foreach ($classrooms as $classroom) {
            Classroom::create($classroom);
        }

        // Create subjects
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH'],
            ['name' => 'English Language', 'code' => 'ENG'],
            ['name' => 'Science', 'code' => 'SCI'],
            ['name' => 'Social Studies', 'code' => 'SST'],
            ['name' => 'Creative Arts', 'code' => 'ART'],
            ['name' => 'Physical Education', 'code' => 'PE'],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }

        // Create assessment types (ensuring they sum to 100)
        $assessmentTypes = [
            ['name' => 'First Test', 'max_score' => 20, 'is_active' => true],
            ['name' => 'Second Test', 'max_score' => 20, 'is_active' => true],
            ['name' => 'Final Exam', 'max_score' => 60, 'is_active' => true],
        ];

        foreach ($assessmentTypes as $assessmentType) {
            AssessmentType::create($assessmentType);
        }

        // Create students (10 students distributed across classrooms)
        $classroomIds = Classroom::pluck('id')->toArray();
        
        for ($i = 0; $i < 30; $i++) {
            Student::create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'classroom_id' => fake()->randomElement($classroomIds),
            ]);
        }

        // Create sample scores for some students
        $students = Student::take(10)->get();
        $subjectIds = Subject::pluck('id')->toArray();
        $assessmentTypeIds = AssessmentType::where('is_active', true)->pluck('id')->toArray();

        foreach ($students as $student) {
            // Create scores for each subject
            foreach ($subjectIds as $subjectId) {
                // Create scores for each assessment type
                foreach ($assessmentTypeIds as $assessmentTypeId) {
                    $assessmentType = AssessmentType::find($assessmentTypeId);
                    
                    Score::create([
                        'student_id' => $student->id,
                        'subject_id' => $subjectId,
                        'assessment_type_id' => $assessmentTypeId,
                        'score' => fake()->numberBetween(0, $assessmentType->max_score),
                    ]);
                }
            }
        }
    }
}
