<?php

namespace Database\Seeders;

use App\Models\EvaluationSetting;
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

        // Create evaluation settings (CA: 40, Exam: 60 = 100 total)
        EvaluationSetting::create([
            'academic_session_id' => $session->id,
            'name' => 'Continuous Assessment',
            'max_score' => 40,
        ]);

        EvaluationSetting::create([
            'academic_session_id' => $session->id,
            'name' => 'Examination',
            'max_score' => 60,
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

        // Create score headers for the session/term
        $scoreHeaders = [];
        $scoreHeaders[] = \App\Models\ScoreHeader::create([
            'school_class_id' => $createdClassrooms[0]->id,
            'session' => '2024/2025',
            'term' => 1,
            'name' => 'CA1',
            'max_score' => 20,
        ]);
        $scoreHeaders[] = \App\Models\ScoreHeader::create([
            'school_class_id' => $createdClassrooms[0]->id,
            'session' => '2024/2025',
            'term' => 1,
            'name' => 'CA2',
            'max_score' => 20,
        ]);
        $scoreHeaders[] = \App\Models\ScoreHeader::create([
            'school_class_id' => $createdClassrooms[0]->id,
            'session' => '2024/2025',
            'term' => 1,
            'name' => 'Exam',
            'max_score' => 60,
        ]);

        // Create scores for all students using new schema
        $students = Student::all();
        $subjectIds = Subject::pluck('id')->toArray();

        foreach ($students as $student) {
            foreach ($subjectIds as $subjectId) {
                // Create scores for each header
                foreach ($scoreHeaders as $header) {
                    $maxScore = $header->max_score;
                    $value = fake()->numberBetween((int)($maxScore * 0.7), $maxScore);
                    
                    Score::create([
                        'student_id' => $student->id,
                        'subject_id' => $subjectId,
                        'classroom_id' => $student->classroom_id,
                        'score_header_id' => $header->id,
                        'session' => '2024/2025',
                        'term' => 1,
                        'value' => $value,
                    ]);
                }
            }
        }

        // Results will be automatically calculated by ScoreObserver
        $this->command->info('Seeding completed. Results calculated automatically.');
    }
}
