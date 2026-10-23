<?php

declare(strict_types=1);

use App\Http\Controllers\ResultPdfController;
use App\Models\Classroom;
use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\ResultPin;
use App\Models\ResultPinBatch;
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
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = Session::factory()->create([
        'name'      => '2025/2026',
        'is_active' => true,
    ]);

    $this->term = Term::factory()->create([
        'session_id' => $this->session->id,
        'name'       => 'First Term',
        'order'      => 1,
        'is_active'  => true,
    ]);

    $this->classroom = Classroom::factory()->create(['name' => 'JSS 1']);

    $this->studentUser = User::factory()->create([
        'role'             => 'student',
        'is_primary_admin' => false,
        'is_active'        => true,
    ]);

    $this->student = Student::factory()->create([
        'user_id'          => $this->studentUser->id,
        'gender'           => 'female',
        'date_of_birth'    => '2012-05-14',
        'admission_number' => 'ADM-2025-001',
    ]);

    $this->enrollment = StudentEnrollment::create([
        'student_id'   => $this->student->id,
        'classroom_id' => $this->classroom->id,
        'session_id'   => $this->session->id,
    ]);

    $this->structure = ClassScoreStructure::create([
        'class_id'    => $this->classroom->id,
        'session_id'  => $this->session->id,
        'term_id'     => $this->term->id,
        'total_score' => 100,
    ]);

    $this->scoreHead = ScoreHead::create([
        'name'      => '1st CA',
        'max_score' => 40,
    ]);

    ClassScoreStructureItem::create([
        'class_score_structure_id' => $this->structure->id,
        'score_head_id'            => $this->scoreHead->id,
        'weight'                   => 40,
    ]);

    $this->subject = Subject::factory()->create(['name' => 'Mathematics']);

    Score::create([
        'student_id'    => $this->student->id,
        'classroom_id'  => $this->classroom->id,
        'subject_id'    => $this->subject->id,
        'score_head_id' => $this->scoreHead->id,
        'session_id'    => $this->session->id,
        'term_id'       => $this->term->id,
        'teacher_id'    => User::factory()->create(['role' => 'teacher'])->id,
        'score'         => 35,
    ]);

    SubjectResult::create([
        'student_id'   => $this->student->id,
        'classroom_id' => $this->classroom->id,
        'subject_id'   => $this->subject->id,
        'session_id'   => $this->session->id,
        'term_id'      => $this->term->id,
        'total'        => 85,
        'grade'        => 'A',
        'remark'       => 'Excellent',
        'position'     => 1,
        'is_published' => true,
    ]);

    TermResult::create([
        'student_id'       => $this->student->id,
        'classroom_id'     => $this->classroom->id,
        'session_id'       => $this->session->id,
        'term_id'          => $this->term->id,
        'subjects_count'   => 1,
        'grand_total'      => 85,
        'average'          => 85,
        'grade'            => 'A',
        'remark'           => 'Distinction',
        'overall_position' => 1,
        'is_finalized'     => true,
    ]);

    $batch = ResultPinBatch::create([
        'batch_code' => 'BATCH-TEST-02',
        'session_id' => $this->session->id,
        'term_id'    => $this->term->id,
        'quantity'   => 1,
    ]);

    ResultPin::create([
        'batch_id'      => $batch->id,
        'student_id'    => $this->student->id,
        'session_id'    => $this->session->id,
        'term_id'       => $this->term->id,
        'serial_number' => 'SN-TEST-02',
        'pin'           => 'PIN-2025-0001',
        'is_used'       => true,
        'used_at'       => now(),
    ]);

    $this->actingAs($this->studentUser);
});

test('result PDF downloads successfully with standard defaults and generous 18mm margins', function () {
    $request = Request::create('/result/download', 'GET', [
        'session_id' => $this->session->id,
        'term_id'    => $this->term->id,
    ]);

    $response = app(ResultPdfController::class)->download($request);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-type'))->toBe('application/pdf');
});

test('result PDF downloads cleanly when all optional items are toggled off', function () {
    // Disable optional properties
    Setting::updateOrCreate(['key' => 'rc_show_school_logo'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_school_contacts'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_school_motto'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_student_photo'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_gender'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_dob'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_attendance'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_students_examined'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_subject_position'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_subject_teacher_remark'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_performance_summary'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_affective_domain'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_psychomotor_skills'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_school_stamp'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_page_margin_preset'], ['value' => 'generous']);

    $request = Request::create('/result/download', 'GET', [
        'session_id' => $this->session->id,
        'term_id'    => $this->term->id,
    ]);

    $response = app(ResultPdfController::class)->download($request);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-type'))->toBe('application/pdf');
});

test('blade view selectively excludes toggled-off elements and applies margin rule', function () {
    Setting::updateOrCreate(['key' => 'rc_show_students_examined'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_affective_domain'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_psychomotor_skills'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_page_margin_preset'], ['value' => 'custom']);
    Setting::updateOrCreate(['key' => 'rc_page_margin_top'], ['value' => '25']);
    Setting::updateOrCreate(['key' => 'rc_page_margin_right'], ['value' => '20']);
    Setting::updateOrCreate(['key' => 'rc_page_margin_bottom'], ['value' => '25']);
    Setting::updateOrCreate(['key' => 'rc_page_margin_left'], ['value' => '20']);

    $settings = \App\Services\ReportCardSettingsService::loadSettings();
    $marginCss = \App\Services\ReportCardSettingsService::getMarginCss($settings);

    $viewData = [
        'data' => [
            'student' => [
                'name'         => 'Test Student',
                'admission_no' => 'ADM-001',
                'gender'       => 'Female',
                'dob'          => '14/05/2012',
            ],
            'classroom'             => 'JSS 1',
            'session_name'          => '2025/2026',
            'term_name'             => 'First Term',
            'is_cumulative'         => false,
            'score_heads'           => [],
            'subjects'              => [
                [
                    'subject'           => 'Mathematics',
                    'total'             => 85,
                    'grade'             => 'A',
                    'position'          => 1,
                    'remark'            => 'Excellent',
                    'scores'            => [],
                    'students_examined' => 40,
                ],
            ],
            'term_result'           => [
                'grand_total'           => 85,
                'average'               => 85,
                'grade'                 => 'A',
                'remark'                => 'Distinction',
                'overall_position'      => 1,
                'class_population'      => 40,
            ],
            'total_obtainable'      => 100,
            'settings'              => $settings,
            'margin_css'            => $marginCss,
        ],
    ];

    $html = view('results.result-sheet', $viewData)->render();

    // Verify custom margins applied to @page
    expect($html)->toContain('margin: 25mm 20mm 25mm 20mm;')
        // Examined column th excluded
        ->not->toContain('<th style="width:7%;">Examined</th>')
        // Domains section excluded
        ->not->toContain('Affective Domain (Character')
        ->not->toContain('Psychomotor Skills &amp; Activities')
        // Core elements still present
        ->toContain('Mathematics')
        ->toContain('ADM-001');
});
