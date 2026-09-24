<?php

use App\Filament\Pages\ManageClassScoreStructure;
use App\Filament\Resources\ScoreHeadResource\Pages\CreateScoreHead;
use App\Filament\Resources\ScoreHeadResource\Pages\ListScoreHeads;
use App\Filament\Student\Pages\StudentResultPage;
use App\Filament\Teacher\Pages\EnterScores;
use App\Filament\Teacher\Pages\ProcessClassResults;
use App\Filament\Teacher\Widgets\ClassResultPublishingWidget;
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
use App\Models\SubjectResult;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ScoreSpreadsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Live End-to-End Browser Flow: Score Head -> Student Results & PDF', function () {

    it('successfully navigates the complete workflow across all Filament panels and Livewire components', function () {
        // ═════════════════════════════════════════════════════════════════════
        // STEP 1: ADMIN PANEL — Create Score Heads via Filament Resource
        // ═════════════════════════════════════════════════════════════════════
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);

        // Verify Admin accesses Score Heads List
        Livewire::test(ListScoreHeads::class)
            ->assertSuccessful();

        // Admin creates 'Continuous Assessment' (Max: 20)
        Livewire::test(CreateScoreHead::class)
            ->fillForm([
                'name'      => 'Continuous Assessment',
                'max_score' => 20,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $caHead = ScoreHead::where('name', 'Continuous Assessment')->first();
        expect($caHead)->not->toBeNull()->and($caHead->max_score)->toBe(20);

        // Admin creates 'Examination' (Max: 80)
        Livewire::test(CreateScoreHead::class)
            ->fillForm([
                'name'      => 'Examination',
                'max_score' => 80,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $examHead = ScoreHead::where('name', 'Examination')->first();
        expect($examHead)->not->toBeNull()->and($examHead->max_score)->toBe(80);

        // ═════════════════════════════════════════════════════════════════════
        // STEP 2: ADMIN PANEL — Manage & Lock Class Score Structure
        // ═════════════════════════════════════════════════════════════════════
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

        $classroom = Classroom::create(['name' => 'Grade 8 Phoenix', 'class_order' => 8, 'is_active' => true]);
        $math    = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
        $english = Subject::create(['name' => 'English Language', 'code' => 'ENG', 'is_active' => true]);
        $classroom->subjects()->attach([$math->id, $english->id]);

        // Admin configures structure on ManageClassScoreStructure page
        $structurePage = Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $classroom->id)
            ->call('toggleScoreHead', $caHead->id)
            ->call('toggleScoreHead', $examHead->id);

        // Verify total score sums to 100
        expect($structurePage->get('totalScore'))->toBe(100);

        // Admin saves & locks the structure
        $structurePage->call('saveStructure')
            ->assertNotified('Score structure saved successfully.')
            ->call('toggleLock')
            ->assertNotified('Structure locked.');

        $structure = ClassScoreStructure::where('class_id', $classroom->id)->first();
        expect($structure)->not->toBeNull()
            ->and($structure->locked)->toBeTrue()
            ->and($structure->total_score)->toBe(100);

        // ═════════════════════════════════════════════════════════════════════
        // STEP 3: ASSIGN TEACHERS & ENROLL STUDENTS
        // ═════════════════════════════════════════════════════════════════════
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

        // Students
        $sUser1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student1 = Student::create(['user_id' => $sUser1->id, 'full_name' => 'David Adeleke', 'admission_number' => 'STU-001', 'status' => 'active']);
        StudentEnrollment::create(['student_id' => $student1->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

        $sUser2 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student2 = Student::create(['user_id' => $sUser2->id, 'full_name' => 'Tiwa Savage', 'admission_number' => 'STU-002', 'status' => 'active']);
        StudentEnrollment::create(['student_id' => $student2->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

        // ═════════════════════════════════════════════════════════════════════
        // STEP 4: TEACHER PANEL — Live Scorecard & Offline Excel Workflow
        // ═════════════════════════════════════════════════════════════════════
        $this->actingAs($subjectTeacher);

        // Teacher opens EnterScores page
        $enterScoresPage = Livewire::test(EnterScores::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $classroom->id)
            ->set('subject_id', $math->id)
            ->assertSet('loaded', true)
            ->assertSet('structureExists', true)
            ->assertSee('David Adeleke')
            ->assertSee('Tiwa Savage');

        // Teacher exports class Excel file
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
        // David Adeleke (Row 6 - index 0 in alphabetical):
        //   English: CA = 19, Exam = 76 (Total 95 -> Grade A)
        //   Math:    CA = 18, Exam = 72 (Total 90 -> Grade A)
        // Tiwa Savage (Row 7 - index 1 in alphabetical):
        //   English: CA = 16, Exam = 68 (Total 84 -> Grade A)
        //   Math:    CA = 28 (Intentional error! Max 20), Exam = 60
        $sheet->setCellValue('D6', 19);
        $sheet->setCellValue('E6', 76);
        $sheet->setCellValue('F6', 18);
        $sheet->setCellValue('G6', 72);

        $sheet->setCellValue('D7', 16);
        $sheet->setCellValue('E7', 68);
        $sheet->setCellValue('F7', 28); // ERROR > 20
        $sheet->setCellValue('G7', 60);

        $tempPath = tempnam(sys_get_temp_dir(), 'live_browser_') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tempPath);

        // Teacher uploads spreadsheet
        $fakeFile = UploadedFile::fake()->createWithContent('scores.xlsx', file_get_contents($tempPath));

        $enterScoresPage->set('uploadFile', $fakeFile);

        // Verify Staging Modal opened with 1 error flagged
        $enterScoresPage->assertSet('showImportModal', true);
        expect($enterScoresPage->get('stagingData.total_errors'))->toBe(1);

        // Teacher fixes Tiwa's score inline from 28 to 17 (no page reload)
        $enterScoresPage->call('updateStagingScore', 1, "{$math->id}_{$caHead->id}", 17);
        expect($enterScoresPage->get('stagingData.total_errors'))->toBe(0);

        // Teacher submits import
        $enterScoresPage->call('confirmImport')
            ->assertSet('showImportModal', false)
            ->assertNotified('Scores imported successfully!');

        @unlink($tempPath);

        // ═════════════════════════════════════════════════════════════════════
        // STEP 5: STAGE 1 PUBLISH — Subject Teacher Publishes Subject Results
        // ═════════════════════════════════════════════════════════════════════
        // Publish Math
        $enterScoresPage->set('subject_id', $math->id)
            ->call('publishSubject')
            ->assertNotified('Subject scores published successfully!');

        // Switch to English & Publish
        $enterScoresPage->set('subject_id', $english->id)
            ->call('publishSubject')
            ->assertNotified('Subject scores published successfully!');

        // Check Subject Results are published
        expect(SubjectResult::where('classroom_id', $classroom->id)->where('is_published', true)->count())->toBe(4);

        // ═════════════════════════════════════════════════════════════════════
        // STEP 6: STAGE 2 FINALIZE — Class Teacher Processes Class Results
        // ═════════════════════════════════════════════════════════════════════
        $this->actingAs($classTeacher);

        // Class Teacher checks widget progress
        Livewire::test(ClassResultPublishingWidget::class)
            ->assertSee('2 / 2') // 2 of 2 subjects published
            ->assertSee('Pending Finalization');

        // Class Teacher runs finalization on ProcessClassResults page
        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $classroom->id)
            ->call('processClassResults')
            ->assertNotified('Class results processed and published successfully!');

        // Verify Term Results are finalized
        $davidResult = TermResult::where('student_id', $student1->id)->first();
        expect($davidResult)->not->toBeNull()
            ->and((float) $davidResult->grand_total)->toBe(185.0) // 95 + 90
            ->and((float) $davidResult->average)->toBe(92.5)
            ->and($davidResult->overall_position)->toBe(1)
            ->and($davidResult->grade)->toBe('A')
            ->and($davidResult->is_finalized)->toBeTrue();

        $tiwaResult = TermResult::where('student_id', $student2->id)->first();
        expect($tiwaResult)->not->toBeNull()
            ->and((float) $tiwaResult->grand_total)->toBe(161.0) // 84 + 77
            ->and((float) $tiwaResult->average)->toBe(80.5)
            ->and($tiwaResult->overall_position)->toBe(2)
            ->and($tiwaResult->grade)->toBe('A')
            ->and($tiwaResult->is_finalized)->toBeTrue();

        // ═════════════════════════════════════════════════════════════════════
        // STEP 7: STUDENT PORTAL — Student views Scorecard & Downloads PDF
        // ═════════════════════════════════════════════════════════════════════
        $this->actingAs($sUser1);

        // David opens student result page
        Livewire::test(StudentResultPage::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->assertSet('loaded', true)
            ->assertSee('David Adeleke')
            ->assertSee('Grade 8 Phoenix')
            ->assertSee('185')   // Grand Total
            ->assertSee('92.5%') // Average
            ->assertSee('1')     // Position
            ->assertDontSee('No results found');

        // David downloads official PDF result sheet
        $pdfResponse = $this->get(route('student.result-pdf', [
            'session_id' => $session->id,
            'term_id'    => $term->id,
        ]));

        $pdfResponse->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        expect(strlen($pdfResponse->getContent()))->toBeGreaterThan(1000);
    });

});
