<?php

use App\Filament\Student\Pages\StudentResultPage;
use App\Filament\Teacher\Pages\ProcessClassResults;
use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\ClassTeacherAssignment;
use App\Models\ClassTermApproval;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ClassRemarksSpreadsheetService;
use App\Services\ResultCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function setupPrincipalCommentContext(): array
{
    $session = Session::create([
        'name'       => '2025-2026',
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

    $classroom = Classroom::create(['name' => 'JSS 3 Platinum', 'class_order' => 3, 'is_active' => true]);

    $math = Subject::create(['name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);
    $eng  = Subject::create(['name' => 'English Language', 'code' => 'ENG', 'is_active' => true]);
    $classroom->subjects()->attach([$math->id, $eng->id]);

    $exam = ScoreHead::create(['name' => 'Exam', 'max_score' => 100, 'is_active' => true]);

    $structure = ClassScoreStructure::create([
        'class_id'    => $classroom->id,
        'session_id'  => $session->id,
        'term_id'     => $term->id,
        'total_score' => 100,
        'locked'      => true,
    ]);

    ClassScoreStructureItem::create([
        'class_score_structure_id' => $structure->id,
        'score_head_id'            => $exam->id,
    ]);

    $admin        = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $classTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

    ClassTeacherAssignment::create([
        'teacher_id' => $classTeacher->id,
        'class_id'   => $classroom->id,
        'session_id' => $session->id,
    ]);

    foreach ([$math->id, $eng->id] as $subjId) {
        TeacherSubjectAssignment::create([
            'teacher_id'   => $classTeacher->id,
            'subject_id'   => $subjId,
            'classroom_id' => $classroom->id,
            'session_id'   => $session->id,
            'term_id'      => $term->id,
        ]);
    }

    // Student A (High performer: Grade A)
    $uA = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $studentA = Student::create(['user_id' => $uA->id, 'full_name' => 'Adaeze Obi', 'admission_number' => 'JSS3-001', 'status' => 'active']);
    StudentEnrollment::create(['student_id' => $studentA->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    // Student B (Average performer: Grade C)
    $uB = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $studentB = Student::create(['user_id' => $uB->id, 'full_name' => 'Emeka Okafor', 'admission_number' => 'JSS3-002', 'status' => 'active']);
    StudentEnrollment::create(['student_id' => $studentB->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    // Student C (Struggling: Grade F)
    $uC = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $studentC = Student::create(['user_id' => $uC->id, 'full_name' => 'Tunde Bakare', 'admission_number' => 'JSS3-003', 'status' => 'active']);
    StudentEnrollment::create(['student_id' => $studentC->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    // Enter Math and Eng scores
    // Adaeze: 85 and 90 -> average 87.5 (Grade A)
    // Emeka: 55 and 53 -> average 54.0 (Grade C)
    // Tunde: 30 and 34 -> average 32.0 (Grade F)
    $scores = [
        $studentA->id => [85, 90],
        $studentB->id => [55, 53],
        $studentC->id => [30, 34],
    ];

    foreach ($scores as $sId => [$mScore, $eScore]) {
        Score::create([
            'student_id'    => $sId,
            'classroom_id'  => $classroom->id,
            'subject_id'    => $math->id,
            'score_head_id' => $exam->id,
            'session_id'    => $session->id,
            'term_id'       => $term->id,
            'teacher_id'    => $classTeacher->id,
            'score'         => $mScore,
        ]);
        Score::create([
            'student_id'    => $sId,
            'classroom_id'  => $classroom->id,
            'subject_id'    => $eng->id,
            'score_head_id' => $exam->id,
            'session_id'    => $session->id,
            'term_id'       => $term->id,
            'teacher_id'    => $classTeacher->id,
            'score'         => $eScore,
        ]);
    }

    return compact('session', 'term', 'classroom', 'math', 'eng', 'admin', 'classTeacher', 'studentA', 'studentB', 'studentC', 'uA', 'uB', 'uC');
}

describe('Automated Grade-Tied Principal Comments', function () {

    it('automatically assigns grade-tied principal comment upon class calculation', function () {
        $ctx = setupPrincipalCommentContext();

        // Publish subjects
        app(ResultCalculationService::class)->calculateForSubject($ctx['classroom']->id, $ctx['math']->id, $ctx['session']->id, $ctx['term']->id);
        app(ResultCalculationService::class)->calculateForSubject($ctx['classroom']->id, $ctx['eng']->id, $ctx['session']->id, $ctx['term']->id);

        // Calculate class results
        app(ResultCalculationService::class)->calculateForClass($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

        $trA = TermResult::where('student_id', $ctx['studentA']->id)->first();
        $trB = TermResult::where('student_id', $ctx['studentB']->id)->first();
        $trC = TermResult::where('student_id', $ctx['studentC']->id)->first();

        // Student A (87.5% -> Grade A)
        expect($trA->grade)->toBe('A')
            ->and($trA->principal_comment)->toBe('An excellent and outstanding performance. Keep it up!');

        // Student B (54.0% -> Grade C)
        expect($trB->grade)->toBe('C')
            ->and($trB->principal_comment)->toBe('A good effort, but there is still room for further improvement.');

        // Student C (32.0% -> Grade F)
        expect($trC->grade)->toBe('F')
            ->and($trC->principal_comment)->toBe('A poor result. Needs urgent academic support, seriousness, and dedication.');
    });

    it('allows Admin to manually override a principal comment and preserves it on recalculation', function () {
        $ctx = setupPrincipalCommentContext();

        app(ResultCalculationService::class)->calculateForSubject($ctx['classroom']->id, $ctx['math']->id, $ctx['session']->id, $ctx['term']->id);
        app(ResultCalculationService::class)->calculateForSubject($ctx['classroom']->id, $ctx['eng']->id, $ctx['session']->id, $ctx['term']->id);
        app(ResultCalculationService::class)->calculateForClass($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

        $this->actingAs($ctx['admin']);

        // Admin overrides Student B's comment
        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set("remarks.{$ctx['studentB']->id}.principal_comment", 'Special commendation for great moral conduct.')
            ->call('saveRemarks')
            ->assertNotified('Student remarks & attendance saved successfully!');

        $trB = TermResult::where('student_id', $ctx['studentB']->id)->first();
        expect($trB->principal_comment)->toBe('Special commendation for great moral conduct.');

        // Re-run calculateForClass — custom comment must be preserved!
        app(ResultCalculationService::class)->calculateForClass($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

        $trBRefreshed = TermResult::where('student_id', $ctx['studentB']->id)->first();
        expect($trBRefreshed->principal_comment)->toBe('Special commendation for great moral conduct.');
    });

    it('includes principal comment in spreadsheet export and respects updates on import', function () {
        $ctx = setupPrincipalCommentContext();

        app(ResultCalculationService::class)->calculateForSubject($ctx['classroom']->id, $ctx['math']->id, $ctx['session']->id, $ctx['term']->id);
        app(ResultCalculationService::class)->calculateForSubject($ctx['classroom']->id, $ctx['eng']->id, $ctx['session']->id, $ctx['term']->id);
        app(ResultCalculationService::class)->calculateForClass($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

        $service = app(ClassRemarksSpreadsheetService::class);
        $spreadsheet = $service->generateTemplate($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

        $sheet = $spreadsheet->getActiveSheet();

        // Column I header should be Principal's Comment
        expect($sheet->getCell('I4')->getValue())->toBe("Principal's Comment");
        expect($sheet->getCell('I5')->getValue())->toBe('An excellent and outstanding performance. Keep it up!');

        // Update row 5 with custom principal comment
        $sheet->setCellValue('I5', 'Gold medalist student. Proud of you!');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_princ_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $result = $service->importSpreadsheet($tempPath, $ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);
        expect($result['updated'])->toBe(3);

        $trA = TermResult::where('student_id', $ctx['studentA']->id)->first();
        expect($trA->principal_comment)->toBe('Gold medalist student. Proud of you!');

        @unlink($tempPath);
    });

    it('renders principal comment in report card PDF and student portal', function () {
        $ctx = setupPrincipalCommentContext();

        app(ResultCalculationService::class)->calculateForSubject($ctx['classroom']->id, $ctx['math']->id, $ctx['session']->id, $ctx['term']->id);
        app(ResultCalculationService::class)->calculateForSubject($ctx['classroom']->id, $ctx['eng']->id, $ctx['session']->id, $ctx['term']->id);
        app(ResultCalculationService::class)->calculateForClass($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

        // Approve results
        ClassTermApproval::create([
            'classroom_id'        => $ctx['classroom']->id,
            'session_id'          => $ctx['session']->id,
            'term_id'             => $ctx['term']->id,
            'times_school_opened' => 110,
            'next_term_begins'    => 'January 2026',
            'status'              => 'approved',
            'approved_at'         => now(),
        ]);

        // Create used PIN for student portal access
        $pinBatch = \App\Models\ResultPinBatch::create([
            'batch_code' => 'BATCH-TEST-PC',
            'session_id' => $ctx['session']->id,
            'term_id'    => $ctx['term']->id,
            'quantity'   => 1,
        ]);
        \App\Models\ResultPin::create([
            'batch_id'      => $pinBatch->id,
            'student_id'    => $ctx['studentA']->id,
            'session_id'    => $ctx['session']->id,
            'term_id'       => $ctx['term']->id,
            'serial_number' => 'SN-PC-01',
            'pin'           => '123456',
            'is_used'       => true,
            'used_at'       => now(),
        ]);

        // Student Portal
        $this->actingAs($ctx['uA']);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->assertSet('loaded', true)
            ->assertSet('resultData.term_result.principal_comment', 'An excellent and outstanding performance. Keep it up!');

        // PDF Download
        $response = $this->get(route('student.result-pdf', [
            'session_id' => $ctx['session']->id,
            'term_id'    => $ctx['term']->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    });

});
