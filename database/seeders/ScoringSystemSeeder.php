<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Classroom;
use App\Models\EvaluationSetting;
use App\Models\Score;
use App\Models\ScoreHeader;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScoringSystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating classrooms...');
        $classrooms = $this->createClassrooms();
        
        $this->command->info('Creating subjects...');
        $subjects = $this->createSubjects();
        
        $this->command->info('Creating teachers...');
        $teachers = $this->createTeachers($classrooms);
        
        $this->command->info('Creating academic sessions and terms...');
        [$sessions, $terms] = $this->createSessionsAndTerms();
        
        $this->command->info('Creating teacher assignments...');
        $this->createTeacherAssignments($teachers, $classrooms, $subjects, $sessions);
        
        $this->command->info('Creating students...');
        $this->createStudents($classrooms);
        
        $this->command->info('Creating score headers...');
        $scoreHeaders = $this->createScoreHeaders($classrooms, $sessions, $terms);
        
        $this->command->info('Creating scores...');
        $this->createScores($scoreHeaders);
        
        $this->command->info('Seeding completed successfully! Results will be calculated automatically.');
    }

    private function createClassrooms(): array
    {
        $classroomNames = [
            'Reception',
            'Year 1',
            'Year 2',
            'Year 3',
            'Year 4',
            'Year 5',
            'Year 6',
        ];

        $classrooms = [];
        foreach ($classroomNames as $name) {
            $classrooms[] = Classroom::create(['name' => $name]);
        }

        return $classrooms;
    }

    private function createSubjects(): array
    {
        $subjectData = [
            ['name' => 'Mathematics', 'code' => 'MATH'],
            ['name' => 'English Language', 'code' => 'ENG'],
            ['name' => 'Science', 'code' => 'SCI'],
            ['name' => 'Social Studies', 'code' => 'SST'],
            ['name' => 'Creative Arts', 'code' => 'ART'],
            ['name' => 'Physical Education', 'code' => 'PE'],
            ['name' => 'Computer Studies', 'code' => 'ICT'],
            ['name' => 'Religious Studies', 'code' => 'CRS'],
        ];

        $subjects = [];
        foreach ($subjectData as $data) {
            $subjects[] = Subject::create($data);
        }

        return $subjects;
    }

    private function createTeachers(array $classrooms): array
    {
        $teacherData = [
            ['name' => 'Mrs. Sarah Johnson', 'email' => 'sarah.johnson@wkfs.com'],
            ['name' => 'Mr. David Brown', 'email' => 'david.brown@wkfs.com'],
            ['name' => 'Ms. Emily Davis', 'email' => 'emily.davis@wkfs.com'],
            ['name' => 'Mr. Michael Wilson', 'email' => 'michael.wilson@wkfs.com'],
            ['name' => 'Mrs. Jennifer Taylor', 'email' => 'jennifer.taylor@wkfs.com'],
            ['name' => 'Mr. Robert Anderson', 'email' => 'robert.anderson@wkfs.com'],
        ];

        $teachers = [];
        foreach ($teacherData as $index => $data) {
            // Create user account
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]);

            // Create staff record
            $staff = Staff::create([
                'name' => $data['name'],
                'role' => 'Class Teacher',
                'bio' => 'Experienced educator dedicated to student success.',
                'user_id' => $user->id,
            ]);

            // Assign as class teacher to classroom
            if (isset($classrooms[$index])) {
                $classrooms[$index]->update(['staff_id' => $staff->id]);
            }

            $teachers[] = $user;
        }

        return $teachers;
    }

    private function createSessionsAndTerms(): array
    {
        $sessions = [];
        $terms = [];

        // Current session: 2024/2025
        $currentSession = AcademicSession::create([
            'name' => '2024/2025',
            'start_date' => now()->startOfYear()->subMonths(4), // September 2024
            'end_date' => now()->startOfYear()->addMonths(8), // August 2025
            'is_current' => true,
        ]);
        $sessions[] = $currentSession;

        // Previous session: 2023/2024
        $previousSession = AcademicSession::create([
            'name' => '2023/2024',
            'start_date' => now()->startOfYear()->subMonths(16), // September 2023
            'end_date' => now()->startOfYear()->subMonths(4), // August 2024
            'is_current' => false,
        ]);
        $sessions[] = $previousSession;

        // Create terms for current session
        $terms[] = Term::create([
            'name' => 'First Term',
            'academic_session_id' => $currentSession->id,
            'start_date' => now()->startOfYear()->subMonths(4),
            'end_date' => now()->startOfYear()->subMonths(1),
            'is_current' => false,
        ]);

        $terms[] = Term::create([
            'name' => 'Second Term',
            'academic_session_id' => $currentSession->id,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->startOfYear()->addMonths(3),
            'is_current' => true,
        ]);

        $terms[] = Term::create([
            'name' => 'Third Term',
            'academic_session_id' => $currentSession->id,
            'start_date' => now()->startOfYear()->addMonths(4),
            'end_date' => now()->startOfYear()->addMonths(8),
            'is_current' => false,
        ]);

        // Create evaluation settings
        EvaluationSetting::create([
            'academic_session_id' => $currentSession->id,
            'name' => 'Continuous Assessment',
            'max_score' => 40,
        ]);

        EvaluationSetting::create([
            'academic_session_id' => $currentSession->id,
            'name' => 'Examination',
            'max_score' => 60,
        ]);

        return [$sessions, $terms];
    }

    private function createTeacherAssignments(array $teachers, array $classrooms, array $subjects, array $sessions): void
    {
        $currentSession = $sessions[0];

        // Assign teachers to subjects and classrooms
        foreach ($classrooms as $classroomIndex => $classroom) {
            $teacherIndex = $classroomIndex % count($teachers);
            $teacher = $teachers[$teacherIndex];

            // Each teacher teaches 4-6 subjects to their assigned classroom
            $subjectsToAssign = array_slice($subjects, 0, rand(4, 6));

            foreach ($subjectsToAssign as $subject) {
                \DB::table('classroom_subject_teacher')->insert([
                    'staff_id' => $teacher->staff->id,
                    'subject_id' => $subject->id,
                    'classroom_id' => $classroom->id,
                    'session' => $currentSession->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function createStudents(array $classrooms): void
    {
        // Create demo student first
        Student::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'admission_number' => 'STD/2024/001',
            'password' => bcrypt('password'),
            'classroom_id' => $classrooms[0]->id,
        ]);

        // Create 12-15 students per classroom
        $studentNumber = 2;
        foreach ($classrooms as $classroom) {
            $studentsCount = rand(12, 15);
            
            // Skip first student for first classroom (already created demo student)
            if ($classroom->id === $classrooms[0]->id) {
                $studentsCount--;
            }

            for ($i = 0; $i < $studentsCount; $i++) {
                Student::create([
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'classroom_id' => $classroom->id,
                    'admission_number' => sprintf('STD/2024/%03d', $studentNumber),
                    'password' => bcrypt('password'),
                ]);
                $studentNumber++;
            }
        }
    }

    private function createScoreHeaders(array $classrooms, array $sessions, array $terms): array
    {
        $scoreHeaders = [];
        $currentSession = $sessions[0];

        // Define score header templates
        $headerTemplates = [
            ['name' => 'CA1', 'max_score' => 10],
            ['name' => 'CA2', 'max_score' => 10],
            ['name' => 'CA3', 'max_score' => 20],
            ['name' => 'Exam', 'max_score' => 60],
        ];

        // Create score headers for each classroom and term
        foreach ($classrooms as $classroom) {
            foreach ($terms as $term) {
                // Only create for current session
                if ($term->academic_session_id !== $currentSession->id) {
                    continue;
                }

                foreach ($headerTemplates as $template) {
                    $scoreHeaders[] = ScoreHeader::create([
                        'school_class_id' => $classroom->id,
                        'session' => $currentSession->name,
                        'term' => $this->getTermNumber($term->name),
                        'name' => $template['name'],
                        'max_score' => $template['max_score'],
                    ]);
                }
            }
        }

        return $scoreHeaders;
    }

    private function createScores(array $scoreHeaders): void
    {
        $students = Student::all();
        $subjects = Subject::all();

        // Group score headers by classroom and term
        $headersByClassAndTerm = [];
        foreach ($scoreHeaders as $header) {
            $key = $header->school_class_id . '_' . $header->term;
            if (!isset($headersByClassAndTerm[$key])) {
                $headersByClassAndTerm[$key] = [];
            }
            $headersByClassAndTerm[$key][] = $header;
        }

        foreach ($students as $student) {
            // Get headers for this student's classroom
            foreach ([1, 2] as $term) { // Only create scores for terms 1 and 2 (term 3 is future)
                $key = $student->classroom_id . '_' . $term;
                
                if (!isset($headersByClassAndTerm[$key])) {
                    continue;
                }

                $headers = $headersByClassAndTerm[$key];

                foreach ($subjects as $subject) {
                    foreach ($headers as $header) {
                        // Generate realistic scores (60-100% of max)
                        $minScore = (int)($header->max_score * 0.6);
                        $maxScore = $header->max_score;
                        $value = rand($minScore, $maxScore);

                        Score::create([
                            'student_id' => $student->id,
                            'subject_id' => $subject->id,
                            'classroom_id' => $student->classroom_id,
                            'score_header_id' => $header->id,
                            'session' => $header->session,
                            'term' => $header->term,
                            'value' => $value,
                        ]);
                    }
                }
            }
        }
    }

    private function getTermNumber(string $termName): int
    {
        return match($termName) {
            'First Term' => 1,
            'Second Term' => 2,
            'Third Term' => 3,
            default => 1,
        };
    }
}
