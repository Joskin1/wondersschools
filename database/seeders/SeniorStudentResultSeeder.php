<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\ClassTeacherAssignment;
use App\Models\ClassTermApproval;
use App\Models\Department;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ResultCalculationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SeniorStudentResultSeeder extends Seeder
{
    public function run(): void
    {
        // 1. School Branding Settings
        Setting::updateOrCreate(['key' => 'school_name'], ['value' => 'Chizylite International Academy']);
        Setting::updateOrCreate(['key' => 'school_short_name'], ['value' => 'Chizylite Academy']);
        Setting::updateOrCreate(['key' => 'school_motto'], ['value' => 'Knowledge, Integrity and Excellence']);
        Setting::updateOrCreate(['key' => 'school_address'], ['value' => 'Plot 12, Wisdom Crest Boulevard, GRA, Lagos, Nigeria']);
        Setting::updateOrCreate(['key' => 'school_email'], ['value' => 'info@chizylite.wonders.test']);
        Setting::updateOrCreate(['key' => 'school_phone'], ['value' => '+234 803 555 0192']);

        // 2. Academic Session & Term
        $session = Session::where('is_active', true)->first() ?? Session::first();
        if (!$session) {
            $session = Session::create([
                'name'       => '2026-2027',
                'start_year' => 2026,
                'end_year'   => 2027,
                'is_active'  => true,
            ]);
        }

        $term = Term::where('is_active', true)->where('session_id', $session->id)->first() ?? Term::where('session_id', $session->id)->first();
        if (!$term) {
            $term = Term::create([
                'session_id' => $session->id,
                'name'       => 'First Term',
                'order'      => 1,
                'is_active'  => true,
            ]);
        }

        // 3. Senior Classroom & Departments
        $classroom = Classroom::where('name', 'SS2')->first() ?? Classroom::find(11);
        if (!$classroom) {
            $classroom = Classroom::create([
                'name'            => 'SS2',
                'class_order'     => 11,
                'is_active'       => true,
                'has_departments' => true,
            ]);
        } else {
            $classroom->update([
                'name'            => 'SS2',
                'has_departments' => true,
                'is_active'       => true,
            ]);
        }

        $scienceDept = Department::firstOrCreate(
            ['name' => 'Science'],
            ['is_active' => true]
        );
        $commercialDept = Department::firstOrCreate(
            ['name' => 'Commercial'],
            ['is_active' => true]
        );
        $artsDept = Department::firstOrCreate(
            ['name' => 'Arts'],
            ['is_active' => true]
        );
        $department = $scienceDept;

        // 4. Score Heads & Structure
        $ca1 = ScoreHead::firstOrCreate(['name' => 'Classwork'], ['max_score' => 10, 'is_active' => true]);
        $ca2 = ScoreHead::firstOrCreate(['name' => 'Test 1'], ['max_score' => 10, 'is_active' => true]);
        $exam = ScoreHead::firstOrCreate(['name' => 'Exam'], ['max_score' => 80, 'is_active' => true]);

        $scoreStructure = ClassScoreStructure::firstOrCreate(
            [
                'class_id'   => $classroom->id,
                'session_id' => $session->id,
                'term_id'    => $term->id,
            ],
            [
                'total_score' => 100,
                'locked'      => true,
            ]
        );

        ClassScoreStructureItem::firstOrCreate([
            'class_score_structure_id' => $scoreStructure->id,
            'score_head_id'            => $ca1->id,
        ]);
        ClassScoreStructureItem::firstOrCreate([
            'class_score_structure_id' => $scoreStructure->id,
            'score_head_id'            => $ca2->id,
        ]);
        ClassScoreStructureItem::firstOrCreate([
            'class_score_structure_id' => $scoreStructure->id,
            'score_head_id'            => $exam->id,
        ]);

        // 5. Senior Core & Elective Subjects
        $subjectNames = [
            'Mathematics'           => 'MATH',
            'English Language'      => 'ENG',
            'Physics'               => 'PHY',
            'Chemistry'             => 'CHEM',
            'Biology'               => 'BIO',
            'Economics'             => 'ECON',
            'Civic Education'       => 'CIVIC',
            'Computer Science'      => 'CS',
            'Financial Accounting'  => 'ACC',
            'Commerce'              => 'COMM',
            'Literature in English' => 'LIT',
            'Government'            => 'GOV',
        ];

        $subjects = [];
        foreach ($subjectNames as $name => $code) {
            $sub = Subject::firstOrCreate(['name' => $name], ['code' => $code, 'is_active' => true]);
            $subjects[$name] = $sub;
            $classroom->subjects()->syncWithoutDetaching([$sub->id]);
        }

        // 6. Faculty Assignments
        $classTeacherUser = User::where('role', 'teacher')->first() ?? User::factory()->create([
            'name'     => 'Dr. Emmanuel Okafor',
            'email'    => 'teacher@wonders.test',
            'password' => Hash::make('password'),
            'role'     => 'teacher',
        ]);

        ClassTeacherAssignment::updateOrCreate([
            'teacher_id' => $classTeacherUser->id,
            'session_id' => $session->id,
        ], [
            'class_id'      => $classroom->id,
            'department_id' => $scienceDept->id,
        ]);

        foreach ($subjects as $sub) {
            TeacherSubjectAssignment::firstOrCreate([
                'subject_id'   => $sub->id,
                'classroom_id' => $classroom->id,
                'session_id'   => $session->id,
                'term_id'      => $term->id,
            ], [
                'teacher_id' => $classTeacherUser->id,
            ]);
        }

        $adminUser = User::where('role', 'admin')->first() ?? User::factory()->create([
            'name'     => 'Principal Admin',
            'email'    => 'admin@wonders.test',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        // 7. Premier Senior Student
        $studentUser = User::updateOrCreate(
            ['email' => 'student@wonders.test'],
            [
                'name'              => 'Chidubem Alexander Okafor',
                'password'          => Hash::make('password'),
                'role'              => 'student',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        $student = Student::updateOrCreate(
            ['user_id' => $studentUser->id],
            [
                'full_name'        => 'Chidubem Alexander Okafor',
                'admission_number' => 'WND/2026/SS2/007',
                'gender'           => 'male',
                'date_of_birth'    => '2009-04-15',
                'status'           => 'active',
                'is_portal_active' => true,
                'activated_at'     => now(),
                'parent_name'      => 'Engr. & Mrs. K. Okafor',
                'parent_phone'     => '+234 803 777 8899',
                'parent_email'     => 'parents.okafor@wonders.test',
            ]
        );

        StudentEnrollment::updateOrCreate(
            [
                'student_id'   => $student->id,
                'classroom_id' => $classroom->id,
                'session_id'   => $session->id,
            ],
            [
                'department_id' => $department->id,
            ]
        );

        // 8. Peer Students across Departments
        $sciencePeerNames = [
            ['Zainab Aisha Mohammed', 'WND/2026/SS2/008', 'female'],
            ['Tunde Babatunde Adeleke', 'WND/2026/SS2/009', 'male'],
            ['Khadijah Al-Hassan', 'WND/2026/SS2/010', 'female'],
            ['Somtochukwu David Okoli', 'WND/2026/SS2/011', 'male'],
        ];

        $commercialPeerNames = [
            ['Bimbo Adeleke', 'WND/2026/SS2/COM01', 'female'],
            ['Femi Adebayo', 'WND/2026/SS2/COM02', 'male'],
            ['Grace Danjuma', 'WND/2026/SS2/COM03', 'female'],
        ];

        $artsPeerNames = [
            ['Habiba Yusuf', 'WND/2026/SS2/ART01', 'female'],
            ['Ifeanyi Eze', 'WND/2026/SS2/ART02', 'male'],
        ];

        $peerStudents = [];

        // Science peers
        foreach ($sciencePeerNames as $idx => [$pName, $pAdm, $pGen]) {
            $pStu = Student::where('admission_number', $pAdm)->first();
            if (! $pStu) {
                $pUser = User::firstOrCreate(
                    ['email' => "sci_peer{$idx}@wonders.test"],
                    ['name' => $pName, 'password' => Hash::make('password'), 'role' => 'student', 'is_active' => true]
                );
                $pStu = Student::create([
                    'user_id'          => $pUser->id,
                    'full_name'        => $pName,
                    'admission_number' => $pAdm,
                    'gender'           => $pGen,
                    'status'           => 'active',
                    'is_portal_active' => true,
                ]);
            }
            StudentEnrollment::updateOrCreate([
                'student_id'    => $pStu->id,
                'classroom_id'  => $classroom->id,
                'session_id'    => $session->id,
            ], [
                'department_id' => $scienceDept->id,
            ]);
            $peerStudents[] = ['student' => $pStu, 'dept' => 'science'];
        }

        // Commercial peers
        foreach ($commercialPeerNames as $idx => [$pName, $pAdm, $pGen]) {
            $pStu = Student::where('admission_number', $pAdm)->first();
            if (! $pStu) {
                $pUser = User::firstOrCreate(
                    ['email' => "com_peer{$idx}@wonders.test"],
                    ['name' => $pName, 'password' => Hash::make('password'), 'role' => 'student', 'is_active' => true]
                );
                $pStu = Student::create([
                    'user_id'          => $pUser->id,
                    'full_name'        => $pName,
                    'admission_number' => $pAdm,
                    'gender'           => $pGen,
                    'status'           => 'active',
                    'is_portal_active' => true,
                ]);
            }
            StudentEnrollment::updateOrCreate([
                'student_id'    => $pStu->id,
                'classroom_id'  => $classroom->id,
                'session_id'    => $session->id,
            ], [
                'department_id' => $commercialDept->id,
            ]);
            $peerStudents[] = ['student' => $pStu, 'dept' => 'commercial'];
        }

        // Arts peers
        foreach ($artsPeerNames as $idx => [$pName, $pAdm, $pGen]) {
            $pStu = Student::where('admission_number', $pAdm)->first();
            if (! $pStu) {
                $pUser = User::firstOrCreate(
                    ['email' => "art_peer{$idx}@wonders.test"],
                    ['name' => $pName, 'password' => Hash::make('password'), 'role' => 'student', 'is_active' => true]
                );
                $pStu = Student::create([
                    'user_id'          => $pUser->id,
                    'full_name'        => $pName,
                    'admission_number' => $pAdm,
                    'gender'           => $pGen,
                    'status'           => 'active',
                    'is_portal_active' => true,
                ]);
            }
            StudentEnrollment::updateOrCreate([
                'student_id'    => $pStu->id,
                'classroom_id'  => $classroom->id,
                'session_id'    => $session->id,
            ], [
                'department_id' => $artsDept->id,
            ]);
            $peerStudents[] = ['student' => $pStu, 'dept' => 'arts'];
        }

        // 9. Score distributions
        $chidubemScores = [
            'Mathematics'      => ['ca1' => 10, 'ca2' => 9,  'exam' => 75], // 94
            'English Language' => ['ca1' => 9,  'ca2' => 9,  'exam' => 72], // 90
            'Physics'          => ['ca1' => 10, 'ca2' => 10, 'exam' => 76], // 96
            'Chemistry'        => ['ca1' => 9,  'ca2' => 9,  'exam' => 73], // 91
            'Biology'          => ['ca1' => 9,  'ca2' => 8,  'exam' => 71], // 88
            'Economics'        => ['ca1' => 8,  'ca2' => 9,  'exam' => 68], // 85
            'Civic Education'  => ['ca1' => 9,  'ca2' => 9,  'exam' => 70], // 88
            'Computer Science' => ['ca1' => 10, 'ca2' => 10, 'exam' => 78], // 98
        ];

        foreach ($chidubemScores as $sName => $sc) {
            $sub = $subjects[$sName];
            Score::updateOrCreate([
                'student_id'    => $student->id,
                'classroom_id'  => $classroom->id,
                'subject_id'    => $sub->id,
                'score_head_id' => $ca1->id,
                'session_id'    => $session->id,
                'term_id'       => $term->id,
            ], ['teacher_id' => $classTeacherUser->id, 'score' => $sc['ca1']]);

            Score::updateOrCreate([
                'student_id'    => $student->id,
                'classroom_id'  => $classroom->id,
                'subject_id'    => $sub->id,
                'score_head_id' => $ca2->id,
                'session_id'    => $session->id,
                'term_id'       => $term->id,
            ], ['teacher_id' => $classTeacherUser->id, 'score' => $sc['ca2']]);

            Score::updateOrCreate([
                'student_id'    => $student->id,
                'classroom_id'  => $classroom->id,
                'subject_id'    => $sub->id,
                'score_head_id' => $exam->id,
                'session_id'    => $session->id,
                'term_id'       => $term->id,
            ], ['teacher_id' => $classTeacherUser->id, 'score' => $sc['exam']]);
        }

        // Add scores for peers
        $scienceSubNames = ['Mathematics', 'English Language', 'Physics', 'Chemistry', 'Biology', 'Economics', 'Civic Education', 'Computer Science'];
        $commercialSubNames = ['Mathematics', 'English Language', 'Economics', 'Civic Education', 'Financial Accounting', 'Commerce'];
        $artsSubNames = ['Mathematics', 'English Language', 'Civic Education', 'Literature in English', 'Government', 'Economics'];

        foreach ($peerStudents as $pIdx => $peerInfo) {
            $pStu = $peerInfo['student'];
            $dept = $peerInfo['dept'];
            $targetSubNames = match($dept) {
                'commercial' => $commercialSubNames,
                'arts'       => $artsSubNames,
                default      => $scienceSubNames,
            };

            $baseOffset = 15 + (($pIdx % 4) * 5);
            foreach ($targetSubNames as $sName) {
                $sub = $subjects[$sName];
                $pExam = max(42, min(68, 70 - $baseOffset + rand(-2, 3)));
                Score::updateOrCreate([
                    'student_id'    => $pStu->id,
                    'classroom_id'  => $classroom->id,
                    'subject_id'    => $sub->id,
                    'score_head_id' => $ca1->id,
                    'session_id'    => $session->id,
                    'term_id'       => $term->id,
                ], ['teacher_id' => $classTeacherUser->id, 'score' => rand(6, 8)]);

                Score::updateOrCreate([
                    'student_id'    => $pStu->id,
                    'classroom_id'  => $classroom->id,
                    'subject_id'    => $sub->id,
                    'score_head_id' => $ca2->id,
                    'session_id'    => $session->id,
                    'term_id'       => $term->id,
                ], ['teacher_id' => $classTeacherUser->id, 'score' => rand(6, 8)]);

                Score::updateOrCreate([
                    'student_id'    => $pStu->id,
                    'classroom_id'  => $classroom->id,
                    'subject_id'    => $sub->id,
                    'score_head_id' => $exam->id,
                    'session_id'    => $session->id,
                    'term_id'       => $term->id,
                ], ['teacher_id' => $classTeacherUser->id, 'score' => $pExam]);
            }
        }

        // 10. Compute results using ResultCalculationService
        $calcService = app(ResultCalculationService::class);
        foreach ($subjects as $sub) {
            $calcService->calculateForSubject($classroom->id, $sub->id, $session->id, $term->id);
        }
        $calcService->calculateForClass($classroom->id, $session->id, $term->id);

        // 11. Class Teacher Remarks & Attendance
        TermResult::where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->where('term_id', $term->id)
            ->update([
                'times_present'         => 112,
                'class_teacher_comment' => 'An exceptionally brilliant, proactive, and well-behaved scholar. Has demonstrated consistent mastery across all science disciplines and leads his peers with admirable humility and intellect. Highly recommended!',
            ]);

        // Peer remarks
        foreach ($peerStudents as $peerInfo) {
            $pStu = $peerInfo['student'];
            TermResult::where('student_id', $pStu->id)
                ->where('session_id', $session->id)
                ->where('term_id', $term->id)
                ->update([
                    'times_present'         => rand(98, 108),
                    'class_teacher_comment' => 'Shows keen interest and steady progress in academic work. Keep up the dedication.',
                ]);
        }

        // 12. Admin Approval & Release
        ClassTermApproval::updateOrCreate(
            [
                'classroom_id' => $classroom->id,
                'session_id'   => $session->id,
                'term_id'      => $term->id,
            ],
            [
                'times_school_opened' => 115,
                'next_term_begins'    => 'Monday, 11th January 2027',
                'status'              => 'approved',
                'approved_by'         => $adminUser->id,
                'approved_at'         => now(),
            ]
        );

        $msg = '✅ Mounted Senior Student [Chidubem Alexander Okafor] with full approved results in SS2 Science!';
        if ($this->command) {
            $this->command->info($msg);
        } else {
            echo $msg . PHP_EOL;
        }
    }
}
