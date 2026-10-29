<?php

use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupClassBatchContext(): array
{
    Setting::updateOrCreate(['key' => 'school_name'], ['value' => 'Living Spring School']);
    Setting::updateOrCreate(['key' => 'primary_color'], ['value' => '#1e3a8a']);

    $session = Session::create([
        'name'       => '2025/2026',
        'start_year' => 2025,
        'end_year'   => 2026,
        'is_active'  => true,
    ]);

    $term = Term::create([
        'session_id' => $session->id,
        'name'       => 'First Term',
        'order'      => 1,
        'is_active'  => true,
    ]);

    $classroom = Classroom::create([
        'name'        => 'JSS 1 Gold',
        'class_order' => 1,
        'is_active'   => true,
    ]);

    $math = Subject::create(['name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);
    $eng  = Subject::create(['name' => 'English Language', 'code' => 'ENG', 'is_active' => true]);
    $classroom->subjects()->attach([$math->id, $eng->id]);

    $caHead   = ScoreHead::create(['name' => 'Continuous Assessment', 'max_score' => 30, 'type' => 'ca', 'is_active' => true]);
    $examHead = ScoreHead::create(['name' => 'Terminal Exam', 'max_score' => 70, 'type' => 'exam', 'is_active' => true]);

    $structure = ClassScoreStructure::create([
        'class_id'    => $classroom->id,
        'session_id'  => $session->id,
        'term_id'     => $term->id,
        'total_score' => 100,
        'locked'      => false,
    ]);

    ClassScoreStructureItem::create(['class_score_structure_id' => $structure->id, 'score_head_id' => $caHead->id]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $structure->id, 'score_head_id' => $examHead->id]);

    // Admin user
    $admin = User::factory()->create([
        'role'      => 'admin',
        'is_active' => true,
    ]);

    // Teacher user
    $teacher = User::factory()->create([
        'role'      => 'teacher',
        'is_active' => true,
    ]);

    // Create 2 students in the class
    $student1User = User::factory()->create(['name' => 'Adeyemi Samuel', 'role' => 'student', 'is_active' => true]);
    $student1 = Student::create([
        'user_id'          => $student1User->id,
        'surname'          => 'Adeyemi',
        'first_name'       => 'Samuel',
        'admission_number' => 'LSS001',
        'status'           => 'active',
    ]);
    StudentEnrollment::create([
        'student_id'   => $student1->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
    ]);

    $student2User = User::factory()->create(['name' => 'Ojo Grace', 'role' => 'student', 'is_active' => true]);
    $student2 = Student::create([
        'user_id'          => $student2User->id,
        'surname'          => 'Ojo',
        'first_name'       => 'Grace',
        'admission_number' => 'LSS002',
        'status'           => 'active',
    ]);
    StudentEnrollment::create([
        'student_id'   => $student2->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
    ]);

    // Scores for student 1
    Score::create(['student_id' => $student1->id, 'subject_id' => $math->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $caHead->id, 'score' => 25, 'teacher_id' => $teacher->id]);
    Score::create(['student_id' => $student1->id, 'subject_id' => $math->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $examHead->id, 'score' => 60, 'teacher_id' => $teacher->id]);
    Score::create(['student_id' => $student1->id, 'subject_id' => $eng->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $caHead->id, 'score' => 22, 'teacher_id' => $teacher->id]);
    Score::create(['student_id' => $student1->id, 'subject_id' => $eng->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $examHead->id, 'score' => 58, 'teacher_id' => $teacher->id]);

    // Scores for student 2
    Score::create(['student_id' => $student2->id, 'subject_id' => $math->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $caHead->id, 'score' => 20, 'teacher_id' => $teacher->id]);
    Score::create(['student_id' => $student2->id, 'subject_id' => $math->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $examHead->id, 'score' => 50, 'teacher_id' => $teacher->id]);
    Score::create(['student_id' => $student2->id, 'subject_id' => $eng->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $caHead->id, 'score' => 24, 'teacher_id' => $teacher->id]);
    Score::create(['student_id' => $student2->id, 'subject_id' => $eng->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $examHead->id, 'score' => 62, 'teacher_id' => $teacher->id]);

    // Subject results
    SubjectResult::create(['student_id' => $student1->id, 'subject_id' => $math->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'total' => 85, 'grade' => 'A', 'position' => 1, 'is_published' => true]);
    SubjectResult::create(['student_id' => $student1->id, 'subject_id' => $eng->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'total' => 80, 'grade' => 'A', 'position' => 2, 'is_published' => true]);

    SubjectResult::create(['student_id' => $student2->id, 'subject_id' => $math->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'total' => 70, 'grade' => 'B', 'position' => 2, 'is_published' => true]);
    SubjectResult::create(['student_id' => $student2->id, 'subject_id' => $eng->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'total' => 86, 'grade' => 'A', 'position' => 1, 'is_published' => true]);

    // Term results
    TermResult::create([
        'student_id'       => $student1->id,
        'classroom_id'     => $classroom->id,
        'session_id'       => $session->id,
        'term_id'          => $term->id,
        'subjects_count'   => 2,
        'grand_total'      => 165,
        'average'          => 82.5,
        'grade'            => 'A',
        'overall_position' => 1,
        'is_finalized'     => true,
    ]);

    TermResult::create([
        'student_id'       => $student2->id,
        'classroom_id'     => $classroom->id,
        'session_id'       => $session->id,
        'term_id'          => $term->id,
        'subjects_count'   => 2,
        'grand_total'      => 156,
        'average'          => 78.0,
        'grade'            => 'B',
        'overall_position' => 2,
        'is_finalized'     => true,
    ]);

    return compact('session', 'term', 'classroom', 'admin', 'teacher', 'student1', 'student2');
}

it('requires authentication for class results pdf download', function () {
    $ctx = setupClassBatchContext();

    $response = $this->get(route('admin.results.class-pdf', [
        'session_id'   => $ctx['session->id'] ?? $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'classroom_id' => $ctx['classroom']->id,
    ]));

    $response->assertRedirect('/admin/login');
});

it('blocks non-admin users with 403 Forbidden', function () {
    $ctx = setupClassBatchContext();

    // Teacher attempt
    $response = $this->actingAs($ctx['teacher'])->get(route('admin.results.class-pdf', [
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'classroom_id' => $ctx['classroom']->id,
    ]));

    $response->assertStatus(403);

    // Student attempt
    $studentUser = $ctx['student1']->user;
    $responseStudent = $this->actingAs($studentUser)->get(route('admin.results.class-pdf', [
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'classroom_id' => $ctx['classroom']->id,
    ]));

    $responseStudent->assertStatus(403);
});

it('allows admin to download consolidated class results PDF', function () {
    $ctx = setupClassBatchContext();

    $response = $this->actingAs($ctx['admin'])->get(route('admin.results.class-pdf', [
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'classroom_id' => $ctx['classroom']->id,
    ]));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('JSS_1_Gold_First_Term_2025_2026_Class_Results.pdf');
});

it('allows admin to stream consolidated class results PDF inline', function () {
    $ctx = setupClassBatchContext();

    $response = $this->actingAs($ctx['admin'])->get(route('admin.results.class-pdf', [
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'classroom_id' => $ctx['classroom']->id,
        'stream'       => 1,
    ]));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('inline');
});

it('allows admin to download an individual student report card without ResultPin', function () {
    $ctx = setupClassBatchContext();

    $response = $this->actingAs($ctx['admin'])->get(route('admin.results.student-pdf', [
        'session_id' => $ctx['session']->id,
        'term_id'    => $ctx['term']->id,
        'student_id' => $ctx['student1']->id,
    ]));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('Adeyemi_Samuel');
});

it('builds batch class data with all enrolled students and page break markup', function () {
    $ctx = setupClassBatchContext();

    $service = app(\App\Services\ClassResultPdfService::class);
    $batchData = $service->buildTerminalClassData(
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id
    );

    expect($batchData['studentsData'])->toHaveCount(2);
    expect($batchData['studentsData'][0]['student']['name'])->toBe('Adeyemi Samuel');
    expect($batchData['studentsData'][1]['student']['name'])->toBe('Ojo Grace');

    // Test that the rendered HTML contains both students and page breaks
    $view = view('results.class-result-sheets', $batchData)->render();
    expect($view)->toContain(strtoupper('Adeyemi Samuel'));
    expect($view)->toContain(strtoupper('Ojo Grace'));
    expect($view)->toContain('student-page-wrapper');
    expect($view)->toContain('page-break-after: always');
});

it('allows admin to download consolidated class midterm slips PDF and blocks non-admin', function () {
    $ctx = setupClassBatchContext();

    // Snapshot for midterm mode
    \App\Models\TermScoringSnapshot::create([
        'classroom_id'   => $ctx['classroom']->id,
        'session_id'     => $ctx['session']->id,
        'term_id'        => $ctx['term']->id,
        'mode'           => 'midterm',
        'midterm_weight' => 40,
        'raw_max'        => 80,
        'midterm_heads'  => [
            ['id' => 1, 'name' => 'CA 1', 'max' => 40],
            ['id' => 2, 'name' => 'CA 2', 'max' => 40],
        ],
        'exam_heads'     => [
            ['id' => 3, 'name' => 'Exam', 'max' => 60],
        ],
    ]);

    // Create MidtermTermResults
    \App\Models\MidtermTermResult::create([
        'student_id'       => $ctx['student1']->id,
        'classroom_id'     => $ctx['classroom']->id,
        'session_id'       => $ctx['session']->id,
        'term_id'          => $ctx['term']->id,
        'subjects_count'   => 1,
        'total_obtainable' => 40,
        'total_obtained'   => 35,
        'percentage'       => 87.5,
        'grade'            => 'A',
    ]);

    \App\Models\MidtermTermResult::create([
        'student_id'       => $ctx['student2']->id,
        'classroom_id'     => $ctx['classroom']->id,
        'session_id'       => $ctx['session']->id,
        'term_id'          => $ctx['term']->id,
        'subjects_count'   => 1,
        'total_obtainable' => 40,
        'total_obtained'   => 30,
        'percentage'       => 75.0,
        'grade'            => 'B',
    ]);

    // Teacher attempt blocked
    $responseTeacher = $this->actingAs($ctx['teacher'])->get(route('admin.results.midterm-class-pdf', [
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'classroom_id' => $ctx['classroom']->id,
    ]));
    $responseTeacher->assertStatus(403);

    // Admin download succeeds
    $responseAdmin = $this->actingAs($ctx['admin'])->get(route('admin.results.midterm-class-pdf', [
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'classroom_id' => $ctx['classroom']->id,
    ]));

    $responseAdmin->assertStatus(200);
    $responseAdmin->assertHeader('content-type', 'application/pdf');
    expect($responseAdmin->headers->get('content-disposition'))->toContain('Midterm_Slips.pdf');
});
