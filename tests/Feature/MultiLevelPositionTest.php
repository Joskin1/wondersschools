<?php

use App\Http\Controllers\ResultPdfController;
use App\Models\ClassArm;
use App\Models\Classroom;
use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\Department;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ResultCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = Session::factory()->create(['is_active' => true]);
    $this->term = Term::factory()->create(['session_id' => $this->session->id, 'order' => 1, 'is_active' => true]);

    $this->ca = ScoreHead::create(['name' => 'CA', 'max_score' => 20, 'is_active' => true]);
    $this->exam = ScoreHead::create(['name' => 'Exam', 'max_score' => 80, 'is_active' => true]);

    $this->math = Subject::create(['name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);
    $this->eng = Subject::create(['name' => 'English Language', 'code' => 'ENG', 'is_active' => true]);
});

describe('Senior School Department Multi-Level Positioning', function () {
    it('accurately computes department position and overall class position across departments', function () {
        $seniorClass = Classroom::factory()->create([
            'name' => 'SS 2',
            'has_departments' => true,
        ]);

        $structure = ClassScoreStructure::create([
            'class_id'   => $seniorClass->id,
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'total_score' => 100,
            'locked'     => true,
        ]);
        ClassScoreStructureItem::create(['class_score_structure_id' => $structure->id, 'score_head_id' => $this->exam->id]);

        $scienceDept = Department::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $commercialDept = Department::create(['name' => 'Commercial', 'code' => 'COM', 'is_active' => true]);

        // Student 1: Science (Top overall, 90 avg)
        $s1 = Student::factory()->create(['full_name' => 'Chidubem Science']);
        StudentEnrollment::create([
            'student_id' => $s1->id,
            'classroom_id' => $seniorClass->id,
            'session_id' => $this->session->id,
            'department_id' => $scienceDept->id,
        ]);

        // Student 2: Science (50 avg)
        $s2 = Student::factory()->create(['full_name' => 'Aisha Science']);
        StudentEnrollment::create([
            'student_id' => $s2->id,
            'classroom_id' => $seniorClass->id,
            'session_id' => $this->session->id,
            'department_id' => $scienceDept->id,
        ]);

        // Student 3: Commercial (Top Commercial, 80 avg, 2nd overall)
        $s3 = Student::factory()->create(['full_name' => 'Bimbo Commercial']);
        StudentEnrollment::create([
            'student_id' => $s3->id,
            'classroom_id' => $seniorClass->id,
            'session_id' => $this->session->id,
            'department_id' => $commercialDept->id,
        ]);

        // Student 4: Commercial (40 avg)
        $s4 = Student::factory()->create(['full_name' => 'Femi Commercial']);
        StudentEnrollment::create([
            'student_id' => $s4->id,
            'classroom_id' => $seniorClass->id,
            'session_id' => $this->session->id,
            'department_id' => $commercialDept->id,
        ]);

        // Scores for Math & Eng
        $teacher = User::factory()->create(['role' => 'teacher']);
        $scorePlan = [
            $s1->id => 90, // total 180
            $s2->id => 50, // total 100
            $s3->id => 80, // total 160
            $s4->id => 40, // total 80
        ];

        foreach ($scorePlan as $stId => $sc) {
            foreach ([$this->math, $this->eng] as $subj) {
                Score::create([
                    'student_id' => $stId,
                    'classroom_id' => $seniorClass->id,
                    'subject_id' => $subj->id,
                    'score_head_id' => $this->exam->id,
                    'session_id' => $this->session->id,
                    'term_id' => $this->term->id,
                    'teacher_id' => $teacher->id,
                    'score' => $sc,
                ]);
            }
        }

        // Calculate
        $calcService = app(ResultCalculationService::class);
        $calcService->calculateForSubject($seniorClass->id, $this->math->id, $this->session->id, $this->term->id);
        $calcService->calculateForSubject($seniorClass->id, $this->eng->id, $this->session->id, $this->term->id);
        $calcService->calculateForClass($seniorClass->id, $this->session->id, $this->term->id);

        $r1 = TermResult::where('student_id', $s1->id)->where('session_id', $this->session->id)->where('term_id', $this->term->id)->first();
        $r2 = TermResult::where('student_id', $s2->id)->where('session_id', $this->session->id)->where('term_id', $this->term->id)->first();
        $r3 = TermResult::where('student_id', $s3->id)->where('session_id', $this->session->id)->where('term_id', $this->term->id)->first();
        $r4 = TermResult::where('student_id', $s4->id)->where('session_id', $this->session->id)->where('term_id', $this->term->id)->first();

        // Total classroom population
        expect($r1->class_population)->toBe(4);
        expect($r1->overall_position)->toBe(1); // 1st overall
        expect($r1->department_position)->toBe(1); // 1st in Science
        expect($r1->department_population)->toBe(2); // 2 in Science

        expect($r3->class_population)->toBe(4);
        expect($r3->overall_position)->toBe(2); // 2nd overall (behind Chidubem)
        expect($r3->department_position)->toBe(1); // 1st in Commercial!
        expect($r3->department_population)->toBe(2); // 2 in Commercial

        expect($r2->overall_position)->toBe(3); // 3rd overall
        expect($r2->department_position)->toBe(2); // 2nd in Science

        expect($r4->overall_position)->toBe(4); // 4th overall
        expect($r4->department_position)->toBe(2); // 2nd in Commercial
    });
});

describe('Junior School Arm Multi-Level Positioning', function () {
    it('accurately computes arm position and overall class position across arms', function () {
        $juniorClass = Classroom::factory()->create([
            'name' => 'JSS 1',
            'has_departments' => false,
        ]);

        $structure = ClassScoreStructure::create([
            'class_id'   => $juniorClass->id,
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'total_score' => 100,
            'locked'     => true,
        ]);
        ClassScoreStructureItem::create(['class_score_structure_id' => $structure->id, 'score_head_id' => $this->exam->id]);

        $goldArm = ClassArm::create(['classroom_id' => $juniorClass->id, 'name' => 'Gold', 'is_active' => true]);
        $silverArm = ClassArm::create(['classroom_id' => $juniorClass->id, 'name' => 'Silver', 'is_active' => true]);

        // Student A: Gold (95 avg, 1st overall, 1st Gold)
        $sA = Student::factory()->create(['full_name' => 'Emeka Gold']);
        StudentEnrollment::create([
            'student_id' => $sA->id,
            'classroom_id' => $juniorClass->id,
            'session_id' => $this->session->id,
            'class_arm_id' => $goldArm->id,
        ]);

        // Student B: Silver (85 avg, 2nd overall, 1st Silver)
        $sB = Student::factory()->create(['full_name' => 'Fatima Silver']);
        StudentEnrollment::create([
            'student_id' => $sB->id,
            'classroom_id' => $juniorClass->id,
            'session_id' => $this->session->id,
            'class_arm_id' => $silverArm->id,
        ]);

        // Student C: Gold (75 avg, 3rd overall, 2nd Gold)
        $sC = Student::factory()->create(['full_name' => 'Kola Gold']);
        StudentEnrollment::create([
            'student_id' => $sC->id,
            'classroom_id' => $juniorClass->id,
            'session_id' => $this->session->id,
            'class_arm_id' => $goldArm->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $scorePlan = [
            $sA->id => 95,
            $sB->id => 85,
            $sC->id => 75,
        ];

        foreach ($scorePlan as $stId => $sc) {
            Score::create([
                'student_id' => $stId,
                'classroom_id' => $juniorClass->id,
                'subject_id' => $this->math->id,
                'score_head_id' => $this->exam->id,
                'session_id' => $this->session->id,
                'term_id' => $this->term->id,
                'teacher_id' => $teacher->id,
                'score' => $sc,
            ]);
        }

        $calcService = app(ResultCalculationService::class);
        $calcService->calculateForSubject($juniorClass->id, $this->math->id, $this->session->id, $this->term->id);
        $calcService->calculateForClass($juniorClass->id, $this->session->id, $this->term->id);

        $rA = TermResult::where('student_id', $sA->id)->where('session_id', $this->session->id)->where('term_id', $this->term->id)->first();
        $rB = TermResult::where('student_id', $sB->id)->where('session_id', $this->session->id)->where('term_id', $this->term->id)->first();
        $rC = TermResult::where('student_id', $sC->id)->where('session_id', $this->session->id)->where('term_id', $this->term->id)->first();

        expect($rA->class_population)->toBe(3);
        expect($rA->overall_position)->toBe(1);
        expect($rA->arm_position)->toBe(1);
        expect($rA->arm_population)->toBe(2);

        expect($rB->class_population)->toBe(3);
        expect($rB->overall_position)->toBe(2);
        expect($rB->arm_position)->toBe(1);
        expect($rB->arm_population)->toBe(1);

        expect($rC->class_population)->toBe(3);
        expect($rC->overall_position)->toBe(3);
        expect($rC->arm_position)->toBe(2);
        expect($rC->arm_population)->toBe(2);
    });
});

describe('Report Card PDF with Safe Margins and Multi-Level Positions', function () {
    it('renders PDF with department and arm positions and downloads properly', function () {
        $seniorClass = Classroom::factory()->create([
            'name' => 'SS 2',
            'has_departments' => true,
        ]);
        $scienceDept = Department::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);

        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student = Student::factory()->create(['user_id' => $user->id, 'full_name' => 'Test Student']);

        StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $seniorClass->id,
            'session_id' => $this->session->id,
            'department_id' => $scienceDept->id,
        ]);

        TermResult::create([
            'student_id'            => $student->id,
            'classroom_id'          => $seniorClass->id,
            'session_id'            => $this->session->id,
            'term_id'               => $this->term->id,
            'subjects_count'        => 1,
            'grand_total'           => 90,
            'average'               => 90,
            'grade'                 => 'A',
            'remark'                => 'Excellent',
            'overall_position'      => 1,
            'class_population'      => 5,
            'department_position'   => 1,
            'department_population' => 2,
            'is_finalized'          => true,
        ]);

        $this->actingAs($user);

        $request = Request::create('/result/download', 'GET', [
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
        ]);

        $response = app(ResultPdfController::class)->download($request);

        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('content-type'))->toBe('application/pdf');
    });
});
