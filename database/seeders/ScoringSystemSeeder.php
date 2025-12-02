<?php

namespace Database\Seeders;

use App\Models\AssessmentType;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\Staff;
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

        $createdClassrooms = [];
        foreach ($classrooms as $classroom) {
            $createdClassrooms[] = Classroom::create($classroom);
        }

        // Create teacher users and assign them to classrooms
        $teacherNames = [
            'Mrs. Sarah Johnson',
            'Mr. David Brown',
            'Ms. Emily Davis',
            'Mr. Michael Wilson',
        ];

        foreach ($teacherNames as $index => $teacherName) {
            // Create user account for teacher
            $user = User::create([
                'name' => $teacherName,
                'email' => strtolower(str_replace([' ', '.'], ['', ''], $teacherName)) . '@wkfs.com',
                'password' => bcrypt('password'),
            ]);

            // Create staff record linked to user
            $staff = Staff::create([
                'name' => $teacherName,
                'role' => 'Class Teacher',
                'bio' => 'Experienced educator dedicated to student success.',
                'user_id' => $user->id,
            ]);

            // Assign teacher to classroom(s)
            if (isset($createdClassrooms[$index])) {
                $createdClassrooms[$index]->update(['staff_id' => $staff->id]);
            }
            if (isset($createdClassrooms[$index + 4])) {
                $createdClassrooms[$index + 4]->update(['staff_id' => $staff->id]);
            }
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

        // Create academic session and term
        $session = \App\Models\AcademicSession::create([
            'name' => '2024/2025',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->addMonths(9),
            'is_current' => true,
        ]);

        $term = \App\Models\Term::create([
            'name' => 'First Term',
            'academic_session_id' => $session->id,
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subMonths(1),
            'is_current' => true,
        ]);

        // Create demo student with known credentials FIRST
        $demoStudent = Student::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'admission_number' => 'STD/2024/001',
            'password' => bcrypt('password'),
            'classroom_id' => $createdClassrooms[0]->id, // Reception
        ]);

        // Create students with admission numbers (starting from 002)
        $classroomIds = Classroom::pluck('id')->toArray();
        $studentNumber = 2; // Start from 2 since demo student is 001
        
        foreach ($classroomIds as $classroomId) {
            // Skip first classroom's first student since we already created demo student
            $studentsToCreate = ($classroomId == $createdClassrooms[0]->id) ? 9 : 10;
            
            for ($i = 0; $i < $studentsToCreate; $i++) {
                Student::create([
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'classroom_id' => $classroomId,
                    'admission_number' => sprintf('STD/2024/%03d', $studentNumber),
                    'password' => bcrypt('password'),
                ]);
                $studentNumber++;
            }
        }

        // Create scores for all students
        $students = Student::all();
        $subjectIds = Subject::pluck('id')->toArray();
        $assessmentTypeIds = AssessmentType::where('is_active', true)->pluck('id')->toArray();

        foreach ($students as $student) {
            foreach ($subjectIds as $subjectId) {
                foreach ($assessmentTypeIds as $assessmentTypeId) {
                    $assessmentType = AssessmentType::find($assessmentTypeId);
                    // Generate random scores (70-100% of max score for variety)
                    $minScore = (int) ($assessmentType->max_score * 0.7);
                    $scoreValue = fake()->numberBetween($minScore, $assessmentType->max_score);
                    
                    Score::create([
                        'student_id' => $student->id,
                        'subject_id' => $subjectId,
                        'assessment_type_id' => $assessmentTypeId,
                        'academic_session_id' => $session->id,
                        'term_id' => $term->id,
                        'score' => $scoreValue,
                    ]);
                }
            }
        }

        // Results will be automatically calculated by ScoreObserver
        $this->command->info('Seeding completed. Results calculated automatically.');
    }
}
