<?php

use App\Filament\Teacher\Pages\EnterScores;
use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\ClassTeacherAssignment;
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
use App\Services\ScoreSpreadsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function setupSpreadsheetContext(): array
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

    $classroom = Classroom::create(['name' => 'Primary 4 Ruby', 'class_order' => 4, 'is_active' => true]);

    $math    = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
    $english = Subject::create(['name' => 'English Language', 'code' => 'ENG', 'is_active' => true]);

    $classroom->subjects()->attach([$math->id, $english->id]);

    // Score Heads
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

    $teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

    // Assign teacher to Math and English in this class
    TeacherSubjectAssignment::create([
        'teacher_id'   => $teacher->id,
        'subject_id'   => $math->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
        'term_id'      => $term->id,
        'status'       => 'approved',
    ]);
    TeacherSubjectAssignment::create([
        'teacher_id'   => $teacher->id,
        'subject_id'   => $english->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
        'term_id'      => $term->id,
        'status'       => 'approved',
    ]);

    // Enrolled Students
    $u1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $student1 = Student::create(['user_id' => $u1->id, 'full_name' => 'Alice Johnson', 'admission_number' => 'P4-001', 'status' => 'active']);
    StudentEnrollment::create(['student_id' => $student1->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    $u2 = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $student2 = Student::create(['user_id' => $u2->id, 'full_name' => 'Bob Davis', 'admission_number' => 'P4-002', 'status' => 'active']);
    StudentEnrollment::create(['student_id' => $student2->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    return compact('session', 'term', 'classroom', 'math', 'english', 'ca', 'exam', 'teacher', 'student1', 'student2');
}

describe('ScoreSpreadsheetService: Export & Parsing Engine', function () {

    it('generates a valid spreadsheet for a single subject with student info and score heads', function () {
        $ctx = setupSpreadsheetContext();
        $service = app(ScoreSpreadsheetService::class);

        $spreadsheet = $service->generateTemplate(
            $ctx['classroom']->id,
            [$ctx['math']->id],
            $ctx['session']->id,
            $ctx['term']->id
        );

        $sheet = $spreadsheet->getActiveSheet();

        // Banner in A1
        expect($sheet->getCell('A1')->getValue())->toContain('Primary 4 Ruby');

        // Headers in Row 4 & 5
        expect($sheet->getCell('A5')->getValue())->toBe('S/N');
        expect($sheet->getCell('B5')->getValue())->toBe('Admission No');
        expect($sheet->getCell('C5')->getValue())->toBe('Student Full Name');

        // Student rows
        expect($sheet->getCell('B6')->getValue())->toBe('P4-001');
        expect($sheet->getCell('C6')->getValue())->toBe('Alice Johnson');
        expect($sheet->getCell('B7')->getValue())->toBe('P4-002');
        expect($sheet->getCell('C7')->getValue())->toBe('Bob Davis');
    });

    it('generates a multi-subject class spreadsheet when teacher teaches multiple subjects', function () {
        $ctx = setupSpreadsheetContext();
        $service = app(ScoreSpreadsheetService::class);

        $spreadsheet = $service->generateTemplate(
            $ctx['classroom']->id,
            [$ctx['math']->id, $ctx['english']->id],
            $ctx['session']->id,
            $ctx['term']->id
        );

        $sheet = $spreadsheet->getActiveSheet();

        // Row 4 subject headers (alphabetical: English Language then Mathematics)
        expect($sheet->getCell('D4')->getValue())->toBe('ENGLISH LANGUAGE');
        expect($sheet->getCell('F4')->getValue())->toBe('MATHEMATICS');
    });

    it('pre-fills existing scores into the exported template', function () {
        $ctx = setupSpreadsheetContext();

        // Existing score for Alice Math CA
        Score::create([
            'student_id'    => $ctx['student1']->id,
            'classroom_id'  => $ctx['classroom']->id,
            'subject_id'    => $ctx['math']->id,
            'score_head_id' => $ctx['ca']->id,
            'session_id'    => $ctx['session']->id,
            'term_id'       => $ctx['term']->id,
            'teacher_id'    => $ctx['teacher']->id,
            'score'         => 18.5,
        ]);

        $service = app(ScoreSpreadsheetService::class);
        $spreadsheet = $service->generateTemplate(
            $ctx['classroom']->id,
            [$ctx['math']->id],
            $ctx['session']->id,
            $ctx['term']->id
        );

        $sheet = $spreadsheet->getActiveSheet();
        expect((float) $sheet->getCell('D6')->getValue())->toBe(18.5);
    });

});

describe('ScoreSpreadsheetService: Parsing & Staging Validation', function () {

    it('parses valid spreadsheet file into structured staging payload without errors', function () {
        $ctx = setupSpreadsheetContext();
        $service = app(ScoreSpreadsheetService::class);

        // Generate template
        $spreadsheet = $service->generateTemplate(
            $ctx['classroom']->id,
            [$ctx['math']->id],
            $ctx['session']->id,
            $ctx['term']->id
        );

        $sheet = $spreadsheet->getActiveSheet();
        // Fill Alice: CA = 17, Exam = 70
        $sheet->setCellValue('D6', 17);
        $sheet->setCellValue('E6', 70);

        // Save temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'test_parse_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $result = $service->parseAndValidate(
            $tempPath,
            $ctx['classroom']->id,
            [$ctx['math']->id],
            $ctx['session']->id,
            $ctx['term']->id
        );

        @unlink($tempPath);

        expect($result['total_errors'])->toBe(0)
            ->and($result['total_students'])->toBe(2)
            ->and($result['students'][0]['full_name'])->toBe('Alice Johnson')
            ->and($result['students'][0]['scores']["{$ctx['math']->id}_{$ctx['ca']->id}"]['value'])->toBe('17')
            ->and($result['students'][0]['scores']["{$ctx['math']->id}_{$ctx['exam']->id}"]['value'])->toBe('70');
    });

    it('catches out-of-bound scores exceeding max obtainable and flags cell errors', function () {
        $ctx = setupSpreadsheetContext();
        $service = app(ScoreSpreadsheetService::class);

        $spreadsheet = $service->generateTemplate(
            $ctx['classroom']->id,
            [$ctx['math']->id],
            $ctx['session']->id,
            $ctx['term']->id
        );

        $sheet = $spreadsheet->getActiveSheet();
        // Alice CA max is 20, set to 25 (error)
        $sheet->setCellValue('D6', 25);
        // Alice Exam max is 80, set to -5 (error)
        $sheet->setCellValue('E6', -5);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_parse_err_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $result = $service->parseAndValidate(
            $tempPath,
            $ctx['classroom']->id,
            [$ctx['math']->id],
            $ctx['session']->id,
            $ctx['term']->id
        );

        @unlink($tempPath);

        expect($result['total_errors'])->toBe(2)
            ->and($result['students'][0]['has_error'])->toBeTrue()
            ->and($result['students'][0]['scores']["{$ctx['math']->id}_{$ctx['ca']->id}"]['error'])->toContain('Exceeds max of 20')
            ->and($result['students'][0]['scores']["{$ctx['math']->id}_{$ctx['exam']->id}"]['error'])->toContain('Cannot be negative');
    });

});

describe('Livewire EnterScores: Staging Preview, In-Place Correction & Import', function () {

    it('opens staging modal upon Excel upload and allows instant in-place error correction before saving', function () {
        $ctx = setupSpreadsheetContext();
        $service = app(ScoreSpreadsheetService::class);

        // Generate template with one error
        $spreadsheet = $service->generateTemplate(
            $ctx['classroom']->id,
            [$ctx['math']->id],
            $ctx['session']->id,
            $ctx['term']->id
        );
        $sheet = $spreadsheet->getActiveSheet();
        // Alice valid: CA = 15, Exam = 75
        $sheet->setCellValue('D6', 15);
        $sheet->setCellValue('E6', 75);
        // Bob invalid CA = 28 (max is 20)
        $sheet->setCellValue('D7', 28);
        $sheet->setCellValue('E7', 60);

        $tempPath = tempnam(sys_get_temp_dir(), 'livewire_upload_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $fakeFile = UploadedFile::fake()->createWithContent('scores.xlsx', file_get_contents($tempPath));

        $this->actingAs($ctx['teacher']);

        $comp = Livewire::test(EnterScores::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set('subject_id', $ctx['math']->id)
            ->set('uploadFile', $fakeFile);

        // Modal should be open and total errors should be 1 (Bob's CA)
        $comp->assertSet('showImportModal', true);
        expect($comp->get('stagingData.total_errors'))->toBe(1);

        // Teacher fixes Bob's score inline in the modal from 28 to 18
        $scoreKey = "{$ctx['math']->id}_{$ctx['ca']->id}";
        $comp->call('updateStagingScore', 1, $scoreKey, 18);

        // Error should be immediately cleared without page reload!
        expect($comp->get('stagingData.total_errors'))->toBe(0)
            ->and($comp->get('stagingData.students.1.scores.' . $scoreKey . '.error'))->toBeNull();

        // Now teacher commits the import
        $comp->call('confirmImport')
            ->assertSet('showImportModal', false)
            ->assertNotified('Scores imported successfully!');

        // Verify saved scores in database
        $this->assertDatabaseHas('scores', [
            'student_id'    => $ctx['student1']->id,
            'subject_id'    => $ctx['math']->id,
            'score_head_id' => $ctx['ca']->id,
            'score'         => 15,
        ]);
        $this->assertDatabaseHas('scores', [
            'student_id'    => $ctx['student2']->id,
            'subject_id'    => $ctx['math']->id,
            'score_head_id' => $ctx['ca']->id,
            'score'         => 18,
        ]);

        @unlink($tempPath);
    });

    it('blocks import if term results are already finalized for the class', function () {
        $ctx = setupSpreadsheetContext();

        // Finalize term results
        TermResult::create([
            'student_id'     => $ctx['student1']->id,
            'classroom_id'   => $ctx['classroom']->id,
            'session_id'     => $ctx['session']->id,
            'term_id'        => $ctx['term']->id,
            'subjects_count' => 1,
            'grand_total'    => 85,
            'average'        => 85,
            'grade'          => 'A',
            'remark'         => 'Excellent',
            'is_finalized'   => true,
        ]);

        $this->actingAs($ctx['teacher']);

        $service = app(ScoreSpreadsheetService::class);
        $spreadsheet = $service->generateTemplate(
            $ctx['classroom']->id,
            [$ctx['math']->id],
            $ctx['session']->id,
            $ctx['term']->id
        );
        $tempPath = tempnam(sys_get_temp_dir(), 'test_final_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $fakeFile = UploadedFile::fake()->createWithContent('scores.xlsx', file_get_contents($tempPath));

        Livewire::test(EnterScores::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set('subject_id', $ctx['math']->id)
            ->set('uploadFile', $fakeFile)
            ->assertSet('showImportModal', false)
            ->assertNotified('Cannot import: Term results are already finalized for this class.');

        @unlink($tempPath);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Complete End-to-End Lifecycle: Score Head -> Student Result & PDF
// ─────────────────────────────────────────────────────────────────────────────

describe('Complete End-to-End Lifecycle: From Score Head to Student Result & PDF', function () {

    it('executes full student lifecycle: admin creates score heads -> score structure -> offline excel scoring -> subject publish -> class finalization -> student viewing & PDF download', function () {
        // ── 1. Academic Setup ────────────────────────────────────────────────
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

        $classroom = Classroom::create(['name' => 'JSS 1 Diamond', 'class_order' => 1, 'is_active' => true]);

        $math    = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
        $english = Subject::create(['name' => 'English Language', 'code' => 'ENG', 'is_active' => true]);
        $classroom->subjects()->attach([$math->id, $english->id]);

        // ── 2. Admin creates Score Heads ─────────────────────────────────────
        $ca   = ScoreHead::create(['name' => 'Continuous Assessment', 'max_score' => 20, 'is_active' => true]);
        $exam = ScoreHead::create(['name' => 'Examination', 'max_score' => 80, 'is_active' => true]);

        // ── 3. Admin assigns & locks Class Score Structure (total = 100) ──────
        $structure = ClassScoreStructure::create([
            'class_id'    => $classroom->id,
            'session_id'  => $session->id,
            'term_id'     => $term->id,
            'total_score' => 100,
            'locked'      => true,
        ]);
        ClassScoreStructureItem::create(['class_score_structure_id' => $structure->id, 'score_head_id' => $ca->id]);
        ClassScoreStructureItem::create(['class_score_structure_id' => $structure->id, 'score_head_id' => $exam->id]);

        // ── 4. Assign Teachers & Students ────────────────────────────────────
        $admin          = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $subjectTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $classTeacher   = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

        TeacherSubjectAssignment::create([
            'teacher_id'   => $subjectTeacher->id,
            'subject_id'   => $math->id,
            'classroom_id' => $classroom->id,
            'session_id'   => $session->id,
            'term_id'      => $term->id,
            'status'       => 'approved',
        ]);
        TeacherSubjectAssignment::create([
            'teacher_id'   => $subjectTeacher->id,
            'subject_id'   => $english->id,
            'classroom_id' => $classroom->id,
            'session_id'   => $session->id,
            'term_id'      => $term->id,
            'status'       => 'approved',
        ]);

        ClassTeacherAssignment::create([
            'teacher_id' => $classTeacher->id,
            'class_id'   => $classroom->id,
            'session_id' => $session->id,
        ]);

        // Enrolled Students
        $sUser1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student1 = Student::create(['user_id' => $sUser1->id, 'full_name' => 'Emmanuel Adeyemi', 'admission_number' => 'JSS-101', 'status' => 'active']);
        StudentEnrollment::create(['student_id' => $student1->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

        $sUser2 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student2 = Student::create(['user_id' => $sUser2->id, 'full_name' => 'Chiamaka Okonkwo', 'admission_number' => 'JSS-102', 'status' => 'active']);
        StudentEnrollment::create(['student_id' => $student2->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

        // ── 5. Teacher exports Excel template ────────────────────────────────
        $service = app(ScoreSpreadsheetService::class);
        $spreadsheet = $service->generateTemplate(
            $classroom->id,
            [$math->id, $english->id],
            $session->id,
            $term->id,
            $subjectTeacher->id
        );

        $sheet = $spreadsheet->getActiveSheet();

        // Populate scores in Excel:
        // Emmanuel: Math CA = 18, Math Exam = 72 (Total 90 -> Grade A)
        // Emmanuel: Eng CA = 16, Eng Exam = 64 (Total 80 -> Grade A)
        // Chiamaka: Math CA = 15, Math Exam = 55 (Total 70 -> Grade A)
        // Chiamaka: Eng CA = 25 (Exceeds max 20, error!), Eng Exam = 50
        $sheet->setCellValue('D6', 18);
        $sheet->setCellValue('E6', 72);
        $sheet->setCellValue('F6', 16);
        $sheet->setCellValue('G6', 64);

        $sheet->setCellValue('D7', 15);
        $sheet->setCellValue('E7', 55);
        $sheet->setCellValue('F7', 25); // Invalid CA!
        $sheet->setCellValue('G7', 50);

        $tempPath = tempnam(sys_get_temp_dir(), 'e2e_') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tempPath);

        // ── 6. Teacher uploads Excel, fixes error in-place, and commits ───────
        $this->actingAs($subjectTeacher);
        $fakeFile = UploadedFile::fake()->createWithContent('scores.xlsx', file_get_contents($tempPath));

        $comp = Livewire::test(EnterScores::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $classroom->id)
            ->set('subject_id', $math->id)
            ->set('uploadFile', $fakeFile);

        $comp->assertSet('showImportModal', true);
        expect($comp->get('stagingData.total_errors'))->toBe(1);

        // Fix Emmanuel's Math CA inline from 25 to 18 (Emmanuel is index 1 in alphabetical student order)
        $comp->call('updateStagingScore', 1, "{$math->id}_{$ca->id}", 18);
        expect($comp->get('stagingData.total_errors'))->toBe(0);

        // Confirm batch import
        $comp->call('confirmImport')
            ->assertSet('showImportModal', false)
            ->assertNotified('Scores imported successfully!');

        @unlink($tempPath);

        // Verify raw scores in database
        expect(Score::where('student_id', $student1->id)->count())->toBe(4)
            ->and(Score::where('student_id', $student2->id)->count())->toBe(4);

        // ── 7. Stage 1 Publishing: Publish Subject Scores ────────────────────
        app(\App\Services\ResultCalculationService::class)->calculateForSubject($classroom->id, $math->id, $session->id, $term->id);
        app(\App\Services\ResultCalculationService::class)->calculateForSubject($classroom->id, $english->id, $session->id, $term->id);

        // Verify Subject Results are published (Chiamaka is #1 in Math, Emmanuel is #2 in Math)
        $sr2Math = \App\Models\SubjectResult::where('student_id', $student2->id)->where('subject_id', $math->id)->first();
        expect($sr2Math)->not->toBeNull()
            ->and((float) $sr2Math->total)->toBe(80.0)
            ->and($sr2Math->grade)->toBe('A')
            ->and($sr2Math->position)->toBe(1)
            ->and($sr2Math->is_published)->toBeTrue();

        $sr1Math = \App\Models\SubjectResult::where('student_id', $student1->id)->where('subject_id', $math->id)->first();
        expect($sr1Math)->not->toBeNull()
            ->and((float) $sr1Math->total)->toBe(68.0)
            ->and($sr1Math->grade)->toBe('B')
            ->and($sr1Math->position)->toBe(2);

        // ── 8. Stage 2 Finalization: Class Teacher processes Class Results ───
        $this->actingAs($classTeacher);
        app(\App\Services\ResultCalculationService::class)->calculateForClass($classroom->id, $session->id, $term->id);

        // Chiamaka: 90 (Eng) + 80 (Math) = 170.0 (Rank 1st)
        $tr2 = \App\Models\TermResult::where('student_id', $student2->id)->first();
        expect($tr2)->not->toBeNull()
            ->and((float) $tr2->grand_total)->toBe(170.0)
            ->and((float) $tr2->average)->toBe(85.0)
            ->and($tr2->grade)->toBe('A')
            ->and($tr2->overall_position)->toBe(1)
            ->and($tr2->is_finalized)->toBeTrue();

        // Emmanuel: 70 (Eng) + 68 (Math) = 138.0 (Rank 2nd)
        $tr1 = \App\Models\TermResult::where('student_id', $student1->id)->first();
        expect($tr1)->not->toBeNull()
            ->and((float) $tr1->grand_total)->toBe(138.0)
            ->and((float) $tr1->average)->toBe(69.0)
            ->and($tr1->grade)->toBe('B')
            ->and($tr1->overall_position)->toBe(2)
            ->and($tr1->is_finalized)->toBeTrue();

        // ── 9. Student Portal: Student views results & downloads PDF ─────────
        // Test Emmanuel Adeyemi (Rank 2)
        $this->actingAs($sUser1);

        Livewire::test(\App\Filament\Student\Pages\StudentResultPage::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->assertSet('loaded', true)
            ->assertSee('Emmanuel Adeyemi')
            ->assertSee('JSS 1 Diamond')
            ->assertSee('138') // Grand total
            ->assertSee('69%')  // Average
            ->assertDontSee('No results found');

        // Test Chiamaka Okonkwo (Rank 1)
        $this->actingAs($sUser2);

        Livewire::test(\App\Filament\Student\Pages\StudentResultPage::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->assertSet('loaded', true)
            ->assertSee('Chiamaka Okonkwo')
            ->assertSee('JSS 1 Diamond')
            ->assertSee('170') // Grand total
            ->assertSee('85%')  // Average
            ->assertDontSee('No results found');

        // Student PDF Download
        $response = $this->get(route('student.result-pdf', [
            'session_id' => $session->id,
            'term_id'    => $term->id,
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        expect(strlen($response->getContent()))->toBeGreaterThan(1000);
    });

});



