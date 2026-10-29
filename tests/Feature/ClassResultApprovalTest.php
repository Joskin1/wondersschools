<?php

use App\Filament\Student\Pages\StudentResultPage;
use App\Filament\Teacher\Pages\ProcessClassResults;
use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\ClassTeacherAssignment;
use App\Models\ClassTermApproval;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ClassRemarksSpreadsheetService;
use App\Services\ResultCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function setupClassApprovalContext(): array
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

    $classroom = Classroom::create([
        'name'            => 'SS 1 Diamond',
        'class_order'     => 10,
        'is_active'       => true,
        'has_departments' => true,
    ]);

    $department = Department::create([
        'name'      => 'Science',
        'is_active' => true,
    ]);

    $math = Subject::create(['name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);
    $classroom->subjects()->attach([$math->id]);

    $ca   = ScoreHead::create(['name' => 'Continuous Assessment', 'max_score' => 20, 'is_active' => true]);
    $exam = ScoreHead::create(['name' => 'Examination', 'max_score' => 80, 'is_active' => true]);

    $structure = ClassScoreStructure::create([
        'class_id'    => $classroom->id,
        'session_id'  => $session->id,
        'term_id'     => $term->id,
        'total_score' => 100,
        'locked'      => true,
    ]);

    ClassScoreStructureItem::create([
        'class_score_structure_id' => $structure->id,
        'score_head_id'            => $ca->id,
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

    TeacherSubjectAssignment::create([
        'teacher_id'   => $classTeacher->id,
        'subject_id'   => $math->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
        'term_id'      => $term->id,
    ]);

    // Student 1
    $studentUser1 = User::factory()->create(['role' => 'student', 'is_active' => true, 'surname' => 'Chinedu', 'first_name' => 'Eze', 'other_name' => null]);
    $student1 = Student::create([
        'user_id'          => $studentUser1->id,
        'surname'          => 'Chinedu',
        'first_name'       => 'Eze',
        'admission_number' => 'SS1-001',
        'status'           => 'active',
    ]);
    StudentEnrollment::create([
        'student_id'    => $student1->id,
        'classroom_id'  => $classroom->id,
        'session_id'    => $session->id,
        'department_id' => $department->id,
    ]);

    // Student 2
    $studentUser2 = User::factory()->create(['role' => 'student', 'is_active' => true, 'surname' => 'Fatima', 'first_name' => 'Bello', 'other_name' => null]);
    $student2 = Student::create([
        'user_id'          => $studentUser2->id,
        'surname'          => 'Fatima',
        'first_name'       => 'Bello',
        'admission_number' => 'SS1-002',
        'status'           => 'active',
    ]);
    StudentEnrollment::create([
        'student_id'    => $student2->id,
        'classroom_id'  => $classroom->id,
        'session_id'    => $session->id,
        'department_id' => $department->id,
    ]);

    // Add scores for math
    foreach ([$student1->id => 85, $student2->id => 92] as $sId => $scoreVal) {
        Score::create([
            'student_id'    => $sId,
            'classroom_id'  => $classroom->id,
            'subject_id'    => $math->id,
            'score_head_id' => $exam->id,
            'session_id'    => $session->id,
            'term_id'       => $term->id,
            'teacher_id'    => $classTeacher->id,
            'score'         => $scoreVal,
        ]);
    }

    return compact('session', 'term', 'classroom', 'department', 'math', 'admin', 'classTeacher', 'student1', 'student2', 'studentUser1', 'studentUser2');
}

describe('Class Teacher Remarks and Attendance', function () {

    it('allows class teacher to save remarks and attendance inline via web table', function () {
        $ctx = setupClassApprovalContext();

        // Calculate subject and class results first
        app(ResultCalculationService::class)->calculateForSubject(
            $ctx['classroom']->id,
            $ctx['math']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );
        app(ResultCalculationService::class)->calculateForClass(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        $this->actingAs($ctx['classTeacher']);

        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set("remarks.{$ctx['student1']->id}.times_present", 108)
            ->set("remarks.{$ctx['student1']->id}.class_teacher_comment", 'A disciplined and diligent student.')
            ->set("remarks.{$ctx['student2']->id}.times_present", 112)
            ->set("remarks.{$ctx['student2']->id}.class_teacher_comment", 'Excellent academic curiosity.')
            ->call('saveRemarks')
            ->assertNotified('Student remarks & attendance saved successfully!');

        $tr1 = TermResult::where('student_id', $ctx['student1']->id)
            ->where('session_id', $ctx['session']->id)
            ->where('term_id', $ctx['term']->id)
            ->first();

        $tr2 = TermResult::where('student_id', $ctx['student2']->id)
            ->where('session_id', $ctx['session']->id)
            ->where('term_id', $ctx['term']->id)
            ->first();

        expect($tr1->times_present)->toBe(108)
            ->and($tr1->class_teacher_comment)->toBe('A disciplined and diligent student.')
            ->and($tr2->times_present)->toBe(112)
            ->and($tr2->class_teacher_comment)->toBe('Excellent academic curiosity.');
    });

    it('exports a valid .xlsx template with student departments and pre-filled remarks', function () {
        $ctx = setupClassApprovalContext();

        $service = app(ClassRemarksSpreadsheetService::class);
        $spreadsheet = $service->generateTemplate(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        $sheet = $spreadsheet->getActiveSheet();

        // Banner and headers
        expect($sheet->getCell('A1')->getValue())->toContain('SS 1 Diamond');
        expect($sheet->getCell('B4')->getValue())->toBe('Admission No');
        expect($sheet->getCell('C4')->getValue())->toBe('Student Full Name');
        expect($sheet->getCell('D4')->getValue())->toBe('Department');
        expect($sheet->getCell('G4')->getValue())->toBe('Times Present');
        expect($sheet->getCell('H4')->getValue())->toBe('Class Teacher Comment');

        // Check student rows
        expect($sheet->getCell('B5')->getValue())->toBe('SS1-001');
        expect($sheet->getCell('C5')->getValue())->toBe('Chinedu Eze');
        expect($sheet->getCell('D5')->getValue())->toBe('Science');
        expect((int) $sheet->getCell('J5')->getValue())->toBe($ctx['student1']->id);
    });

    it('imports filled .xlsx spreadsheet and bulk updates student remarks and attendance', function () {
        $ctx = setupClassApprovalContext();

        $service = app(ClassRemarksSpreadsheetService::class);
        $spreadsheet = $service->generateTemplate(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        $sheet = $spreadsheet->getActiveSheet();
        // Fill row 5 (Chinedu)
        $sheet->setCellValue('G5', 99);
        $sheet->setCellValue('H5', 'Shows strong leadership and commitment.');
        // Fill row 6 (Fatima)
        $sheet->setCellValue('G6', 105);
        $sheet->setCellValue('H6', 'Consistent top performer in Science.');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_remarks_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $result = $service->importSpreadsheet(
            $tempPath,
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        expect($result['updated'])->toBe(2)
            ->and($result['errors'])->toBeEmpty();

        $tr1 = TermResult::where('student_id', $ctx['student1']->id)->first();
        expect($tr1->times_present)->toBe(99)
            ->and($tr1->class_teacher_comment)->toBe('Shows strong leadership and commitment.');

        $tr2 = TermResult::where('student_id', $ctx['student2']->id)->first();
        expect($tr2->times_present)->toBe(105)
            ->and($tr2->class_teacher_comment)->toBe('Consistent top performer in Science.');

        @unlink($tempPath);
    });

});

describe('Admin Approval & Result Release Workflow', function () {

    it('prevents non-admin class teacher from approving and releasing results', function () {
        $ctx = setupClassApprovalContext();

        $this->actingAs($ctx['classTeacher']);

        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->call('approveClassResults')
            ->assertNotified('Unauthorized action. Admin privileges required.');

        expect(ClassTermApproval::count())->toBe(0);
    });

    it('allows admin to approve and release results with times school opened and next term begins', function () {
        $ctx = setupClassApprovalContext();

        $this->actingAs($ctx['admin']);

        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set('times_school_opened', 114)
            ->set('next_term_begins', '12th January, 2026')
            ->call('approveClassResults')
            ->assertNotified('Class results approved and released to students!');

        $approval = ClassTermApproval::where('classroom_id', $ctx['classroom']->id)
            ->where('session_id', $ctx['session']->id)
            ->where('term_id', $ctx['term']->id)
            ->first();

        expect($approval)->not->toBeNull()
            ->and($approval->status)->toBe('approved')
            ->and($approval->times_school_opened)->toBe(114)
            ->and($approval->next_term_begins)->toBe('12th January, 2026')
            ->and($approval->approved_by)->toBe($ctx['admin']->id)
            ->and($approval->isApproved())->toBeTrue();
    });

    it('allows admin to revert an approval when adjustments are needed', function () {
        $ctx = setupClassApprovalContext();

        ClassTermApproval::create([
            'classroom_id'        => $ctx['classroom']->id,
            'session_id'          => $ctx['session']->id,
            'term_id'             => $ctx['term']->id,
            'times_school_opened' => 110,
            'next_term_begins'    => 'January 2026',
            'status'              => 'approved',
            'approved_by'         => $ctx['admin']->id,
            'approved_at'         => now(),
        ]);

        $this->actingAs($ctx['admin']);

        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->call('unapproveClassResults')
            ->assertNotified('Class result approval reverted.');

        $approval = ClassTermApproval::where('classroom_id', $ctx['classroom']->id)->first();
        expect($approval->status)->toBe('pending')
            ->and($approval->approved_by)->toBeNull();
    });

    it('blocks recalculation and re-approval when results are already released', function () {
        $ctx = setupClassApprovalContext();

        ClassTermApproval::create([
            'classroom_id'        => $ctx['classroom']->id,
            'session_id'          => $ctx['session']->id,
            'term_id'             => $ctx['term']->id,
            'times_school_opened' => 110,
            'next_term_begins'    => 'January 2026',
            'status'              => 'approved',
            'approved_by'         => $ctx['admin']->id,
            'approved_at'         => now(),
        ]);

        $this->actingAs($ctx['admin']);

        // Attempting to recalculate scores should be blocked
        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->call('processClassResults')
            ->assertNotified('Results Already Released');

        // Attempting to re-approve/update should be blocked
        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set('times_school_opened', 115)
            ->set('next_term_begins', 'February 2026')
            ->call('approveClassResults')
            ->assertNotified('Results Already Released');
    });

    it('re-enables calculation and approval after reverting approval', function () {
        $ctx = setupClassApprovalContext();

        ClassTermApproval::create([
            'classroom_id'        => $ctx['classroom']->id,
            'session_id'          => $ctx['session']->id,
            'term_id'             => $ctx['term']->id,
            'times_school_opened' => 110,
            'next_term_begins'    => 'January 2026',
            'status'              => 'approved',
            'approved_by'         => $ctx['admin']->id,
            'approved_at'         => now(),
        ]);

        $this->actingAs($ctx['admin']);

        // Revert approval
        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->call('unapproveClassResults')
            ->assertNotified('Class result approval reverted.')
            // Now recalculation succeeds
            ->call('processClassResults')
            ->assertNotified('Class results processed and published successfully!')
            // And approval succeeds
            ->set('times_school_opened', 112)
            ->set('next_term_begins', '15th January 2026')
            ->call('approveClassResults')
            ->assertNotified('Class results approved and released to students!');

        $approval = ClassTermApproval::where('classroom_id', $ctx['classroom']->id)->first();
        expect($approval->status)->toBe('approved')
            ->and($approval->times_school_opened)->toBe(112);
    });

    it('unapproving class results resets term result is_finalized to false and unlocks score entry for teachers', function () {
        $ctx = setupClassApprovalContext();

        // Calculate subject and class results (finalizes term result)
        app(ResultCalculationService::class)->calculateForSubject(
            $ctx['classroom']->id,
            $ctx['math']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );
        app(ResultCalculationService::class)->calculateForClass(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        // Term results are finalized
        expect(TermResult::where('classroom_id', $ctx['classroom']->id)->first()->is_finalized)->toBeTrue();

        // Admin approves results
        ClassTermApproval::create([
            'classroom_id'        => $ctx['classroom']->id,
            'session_id'          => $ctx['session']->id,
            'term_id'             => $ctx['term']->id,
            'times_school_opened' => 110,
            'next_term_begins'    => 'January 2026',
            'status'              => 'approved',
            'approved_by'         => $ctx['admin']->id,
            'approved_at'         => now(),
        ]);

        // Teacher is blocked from saving scores while finalized
        $examHead = ScoreHead::where('name', 'Examination')->first();
        $this->actingAs($ctx['classTeacher']);
        Livewire::test(\App\Filament\Teacher\Pages\EnterScores::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set('subject_id', $ctx['math']->id)
            ->call('saveScores', $ctx['student1']->id, $examHead->id, '77');

        // Verify score was NOT updated (remains 85.0)
        expect((float) Score::where('student_id', $ctx['student1']->id)->where('score_head_id', $examHead->id)->value('score'))->toBe(85.0);

        // Admin reverts approval
        $this->actingAs($ctx['admin']);
        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->call('unapproveClassResults')
            ->assertNotified('Class result approval reverted.');

        // Term results are now unfinalized
        expect(TermResult::where('classroom_id', $ctx['classroom']->id)->first()->is_finalized)->toBeFalse();

        // Teacher can now successfully edit scores!
        $this->actingAs($ctx['classTeacher']);
        Livewire::test(\App\Filament\Teacher\Pages\EnterScores::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set('subject_id', $ctx['math']->id)
            ->call('saveScores', $ctx['student1']->id, $examHead->id, '77');

        $updatedScore = Score::where('student_id', $ctx['student1']->id)
            ->where('subject_id', $ctx['math']->id)
            ->where('score_head_id', $examHead->id)
            ->first();

        expect($updatedScore)->not->toBeNull()
            ->and((float) $updatedScore->score)->toBe(77.0);
    });

});

describe('Student Portal & Report Card PDF Integration', function () {

    it('hides result on student portal if class approval is pending', function () {
        $ctx = setupClassApprovalContext();

        // Calculate results
        app(ResultCalculationService::class)->calculateForSubject(
            $ctx['classroom']->id,
            $ctx['math']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );
        app(ResultCalculationService::class)->calculateForClass(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        // Mark as pending approval
        ClassTermApproval::create([
            'classroom_id' => $ctx['classroom']->id,
            'session_id'   => $ctx['session']->id,
            'term_id'      => $ctx['term']->id,
            'status'       => 'pending',
        ]);

        $this->actingAs($ctx['studentUser1']);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->assertSet('loaded', false);
    });

    it('hides result on student portal if class approval record does not exist at all', function () {
        $ctx = setupClassApprovalContext();

        // Calculate results
        app(ResultCalculationService::class)->calculateForSubject(
            $ctx['classroom']->id,
            $ctx['math']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );
        app(ResultCalculationService::class)->calculateForClass(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        // DO NOT create ClassTermApproval
        $this->actingAs($ctx['studentUser1']);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->assertSet('loaded', false);
    });

    it('displays complete scorecard and attendance when approved', function () {
        $ctx = setupClassApprovalContext();

        app(ResultCalculationService::class)->calculateForSubject(
            $ctx['classroom']->id,
            $ctx['math']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );
        app(ResultCalculationService::class)->calculateForClass(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        // Teacher records attendance & remark
        TermResult::where('student_id', $ctx['student1']->id)->update([
            'times_present'         => 110,
            'class_teacher_comment' => 'Brilliant academic performance.',
        ]);

        // Admin approves
        ClassTermApproval::create([
            'classroom_id'        => $ctx['classroom']->id,
            'session_id'          => $ctx['session']->id,
            'term_id'             => $ctx['term']->id,
            'times_school_opened' => 114,
            'next_term_begins'    => '12th January, 2026',
            'status'              => 'approved',
            'approved_by'         => $ctx['admin']->id,
            'approved_at'         => now(),
        ]);

        $batch1 = \App\Models\ResultPinBatch::create([
            'batch_code' => 'BATCH-TEST-APP1',
            'session_id' => $ctx['session']->id,
            'term_id'    => $ctx['term']->id,
            'quantity'   => 1,
        ]);
        \App\Models\ResultPin::create([
            'batch_id'      => $batch1->id,
            'student_id'    => $ctx['student1']->id,
            'session_id'    => $ctx['session']->id,
            'term_id'       => $ctx['term']->id,
            'serial_number' => 'SN-APP-01',
            'pin'           => '123456',
            'is_used'       => true,
            'used_at'       => now(),
        ]);

        $this->actingAs($ctx['studentUser1']);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->assertSet('loaded', true)
            ->assertSet('resultData.times_school_opened', 114)
            ->assertSet('resultData.next_term_begins', '12th January, 2026')
            ->assertSet('resultData.term_result.times_present', 110)
            ->assertSet('resultData.term_result.class_teacher_comment', 'Brilliant academic performance.');
    });

    it('blocks PDF download if results are pending admin approval', function () {
        $ctx = setupClassApprovalContext();

        app(ResultCalculationService::class)->calculateForSubject(
            $ctx['classroom']->id,
            $ctx['math']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );
        app(ResultCalculationService::class)->calculateForClass(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        ClassTermApproval::create([
            'classroom_id' => $ctx['classroom']->id,
            'session_id'   => $ctx['session']->id,
            'term_id'      => $ctx['term']->id,
            'status'       => 'pending',
        ]);

        $this->actingAs($ctx['studentUser1']);

        $response = $this->get(route('student.result-pdf', [
            'session_id' => $ctx['session']->id,
            'term_id'    => $ctx['term']->id,
        ]));

        $response->assertStatus(403);
    });

    it('blocks PDF download if class approval record does not exist at all', function () {
        $ctx = setupClassApprovalContext();

        app(ResultCalculationService::class)->calculateForSubject(
            $ctx['classroom']->id,
            $ctx['math']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );
        app(ResultCalculationService::class)->calculateForClass(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        // DO NOT create ClassTermApproval
        $this->actingAs($ctx['studentUser1']);

        $response = $this->get(route('student.result-pdf', [
            'session_id' => $ctx['session']->id,
            'term_id'    => $ctx['term']->id,
        ]));

        $response->assertStatus(403);
    });

    it('successfully downloads PDF report card with attendance, next term date, and teacher remark when approved', function () {
        $ctx = setupClassApprovalContext();

        app(ResultCalculationService::class)->calculateForSubject(
            $ctx['classroom']->id,
            $ctx['math']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );
        app(ResultCalculationService::class)->calculateForClass(
            $ctx['classroom']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        TermResult::where('student_id', $ctx['student1']->id)->update([
            'times_present'         => 105,
            'class_teacher_comment' => 'Remarkable work ethic throughout the term.',
        ]);

        ClassTermApproval::create([
            'classroom_id'        => $ctx['classroom']->id,
            'session_id'          => $ctx['session']->id,
            'term_id'             => $ctx['term']->id,
            'times_school_opened' => 112,
            'next_term_begins'    => '19th January, 2026',
            'status'              => 'approved',
            'approved_by'         => $ctx['admin']->id,
            'approved_at'         => now(),
        ]);

        $batch2 = \App\Models\ResultPinBatch::create([
            'batch_code' => 'BATCH-TEST-APP2',
            'session_id' => $ctx['session']->id,
            'term_id'    => $ctx['term']->id,
            'quantity'   => 1,
        ]);
        \App\Models\ResultPin::create([
            'batch_id'      => $batch2->id,
            'student_id'    => $ctx['student1']->id,
            'session_id'    => $ctx['session']->id,
            'term_id'       => $ctx['term']->id,
            'serial_number' => 'SN-APP-02',
            'pin'           => '654321',
            'is_used'       => true,
            'used_at'       => now(),
        ]);

        $this->actingAs($ctx['studentUser1']);

        $response = $this->get(route('student.result-pdf', [
            'session_id' => $ctx['session']->id,
            'term_id'    => $ctx['term']->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    });

});
