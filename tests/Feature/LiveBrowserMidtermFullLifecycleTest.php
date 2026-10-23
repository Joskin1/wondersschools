<?php

use App\Filament\Pages\ManageClassScoreStructure;
use App\Filament\Pages\ManageMidtermStructure;
use App\Filament\Pages\Settings;
use App\Filament\Student\Pages\StudentResultPage;
use App\Filament\Teacher\Pages\EnterMidtermScores;
use App\Filament\Teacher\Pages\EnterScores;
use App\Filament\Teacher\Pages\ProcessClassResults;
use App\Filament\Teacher\Pages\ProcessMidtermResults;
use App\Models\ClassMidtermConfig;
use App\Models\ClassMidtermConfigHead;
use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\ClassTeacherAssignment;
use App\Models\Classroom;
use App\Models\MidtermClassTermApproval;
use App\Models\MidtermSubjectResult;
use App\Models\MidtermTermResult;
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
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermScoringSnapshot;
use App\Models\User;
use App\Services\MidtermCalculationService;
use App\Services\ResultCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Live End-to-End Mid-Term & Final Term Browser Lifecycle', function () {

    it('successfully executes the full workflow with real scaling cases (JSS 80->40 and SS 60->30), ABS, missing, and excluded policy', function () {

        // ═════════════════════════════════════════════════════════════════════
        // STEP 1: TOGGLE ALLOW MID-TERM RESULT IN SCHOOL SETTINGS
        // ═════════════════════════════════════════════════════════════════════
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);

        Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '0']);

        // Admin toggles ON via Settings page Academic & Results section
        Livewire::test(Settings::class)
            ->set('data.use_midterm_results', true)
            ->set('data.result_decimal_places', 2)
            ->set('data.result_rounding_rule', 'half_up')
            ->set('data.midterm_absent_policy', 'count_as_zero')
            ->call('saveSectionKeys', [
                'use_midterm_results',
                'midterm_absent_policy',
                'result_decimal_places',
                'result_rounding_rule',
            ], 'Academic & Results')
            ->assertNotified('Academic & Results saved successfully');

        expect(Setting::where('key', 'use_midterm_results')->value('value'))->toBe('1');

        // ═════════════════════════════════════════════════════════════════════
        // STEP 2: SETUP ACADEMIC SESSION, SUBJECTS, AND MULTIPLE CLASSES
        // ═════════════════════════════════════════════════════════════════════
        $session = Session::create([
            'name'       => '2026-2027',
            'start_year' => 2026,
            'end_year'   => 2027,
            'is_active'  => true,
        ]);

        $term = Term::create([
            'session_id' => $session->id,
            'name'       => 'First Term',
            'order'      => 1,
            'is_active'  => true,
        ]);

        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);

        // JSS Score Heads: 5 heads totaling 80 raw max
        $jH1 = ScoreHead::create(['name' => 'Attendance', 'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
        $jH2 = ScoreHead::create(['name' => 'Classwork',  'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
        $jH3 = ScoreHead::create(['name' => 'Assignment', 'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
        $jH4 = ScoreHead::create(['name' => 'First Test', 'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
        $jH5 = ScoreHead::create(['name' => 'Project',    'max_score' => 40, 'type' => 'midterm', 'is_active' => true]);
        // JSS Exam Head: 100 - 40 = 60
        $jExam = ScoreHead::create(['name' => 'Term Exam (60%)', 'max_score' => 60, 'type' => 'exam', 'is_active' => true]);

        // SS Score Heads: 5 heads totaling 60 raw max (5, 5, 10, 10, 30)
        $sH1 = ScoreHead::create(['name' => 'Senior Attendance', 'max_score' => 5,  'type' => 'midterm', 'is_active' => true]);
        $sH2 = ScoreHead::create(['name' => 'Senior Assignment', 'max_score' => 5,  'type' => 'midterm', 'is_active' => true]);
        $sH3 = ScoreHead::create(['name' => 'Senior First Test', 'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
        $sH4 = ScoreHead::create(['name' => 'Second Test',        'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
        $sH5 = ScoreHead::create(['name' => 'Midterm Project',    'max_score' => 30, 'type' => 'midterm', 'is_active' => true]);
        // SS Exam Head: 100 - 30 = 70
        $sExam = ScoreHead::create(['name' => 'Senior Term Exam (70%)', 'max_score' => 70, 'type' => 'exam', 'is_active' => true]);

        // 1. JSS 1 Class
        $jss1 = Classroom::create(['name' => 'JSS 1 A', 'class_order' => 1, 'is_active' => true]);
        $jss1->subjects()->attach($math->id);
        $jTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        TeacherSubjectAssignment::create(['teacher_id' => $jTeacher->id, 'subject_id' => $math->id, 'classroom_id' => $jss1->id, 'session_id' => $session->id, 'term_id' => $term->id, 'status' => 'approved']);
        ClassTeacherAssignment::create(['teacher_id' => $jTeacher->id, 'class_id' => $jss1->id, 'session_id' => $session->id]);

        $jMidConfig = ClassMidtermConfig::create(['classroom_id' => $jss1->id, 'midterm_weight' => 40, 'calculation_method' => 'scale_to_weight']);
        foreach ([$jH1, $jH2, $jH3, $jH4, $jH5] as $h) {
            ClassMidtermConfigHead::create(['class_midterm_config_id' => $jMidConfig->id, 'score_head_id' => $h->id]);
        }
        $jExamStruct = ClassScoreStructure::create(['class_id' => $jss1->id, 'session_id' => $session->id, 'term_id' => $term->id, 'total_score' => 60, 'locked' => false]);
        ClassScoreStructureItem::create(['class_score_structure_id' => $jExamStruct->id, 'score_head_id' => $jExam->id]);

        // JSS Students: 4 students (Case 1, Case 2, ABS, Missing)
        $jStudents = [];
        for ($i = 1; $i <= 4; $i++) {
            $u = User::factory()->create(['role' => 'student', 'is_active' => true]);
            $st = Student::create(['user_id' => $u->id, 'first_name' => "JSS_Student{$i}", 'last_name' => 'Test', 'admission_number' => "ADM-JSS-{$i}", 'status' => 'active']);
            StudentEnrollment::create(['student_id' => $st->id, 'classroom_id' => $jss1->id, 'session_id' => $session->id]);
            $jStudents[] = $st;
        }

        // 2. SS 1 Class
        $ss1 = Classroom::create(['name' => 'SS 1 Science', 'class_order' => 2, 'is_active' => true]);
        $ss1->subjects()->attach($math->id);
        $sTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        TeacherSubjectAssignment::create(['teacher_id' => $sTeacher->id, 'subject_id' => $math->id, 'classroom_id' => $ss1->id, 'session_id' => $session->id, 'term_id' => $term->id, 'status' => 'approved']);
        ClassTeacherAssignment::create(['teacher_id' => $sTeacher->id, 'class_id' => $ss1->id, 'session_id' => $session->id]);

        $sMidConfig = ClassMidtermConfig::create(['classroom_id' => $ss1->id, 'midterm_weight' => 30, 'calculation_method' => 'scale_to_weight']);
        foreach ([$sH1, $sH2, $sH3, $sH4, $sH5] as $h) {
            ClassMidtermConfigHead::create(['class_midterm_config_id' => $sMidConfig->id, 'score_head_id' => $h->id]);
        }
        $sExamStruct = ClassScoreStructure::create(['class_id' => $ss1->id, 'session_id' => $session->id, 'term_id' => $term->id, 'total_score' => 70, 'locked' => false]);
        ClassScoreStructureItem::create(['class_score_structure_id' => $sExamStruct->id, 'score_head_id' => $sExam->id]);

        // SS Students: 2 students (Case 3, Excluded policy case)
        $sStudents = [];
        for ($i = 1; $i <= 2; $i++) {
            $u = User::factory()->create(['role' => 'student', 'is_active' => true]);
            $st = Student::create(['user_id' => $u->id, 'first_name' => "SS_Student{$i}", 'last_name' => 'Test', 'admission_number' => "ADM-SS-{$i}", 'status' => 'active']);
            StudentEnrollment::create(['student_id' => $st->id, 'classroom_id' => $ss1->id, 'session_id' => $session->id]);
            $sStudents[] = $st;
        }

        // ═════════════════════════════════════════════════════════════════════
        // STEP 3: SCORE ENTRY & REAL SCALING IN JSS 1 (80 -> 40)
        // ═════════════════════════════════════════════════════════════════════
        $jPage = Livewire::actingAs($jTeacher)
            ->test(EnterMidtermScores::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $jss1->id)
            ->set('subject_id', $math->id)
            ->assertSet('loaded', true)
            ->assertSet('midtermWeight', 40)
            ->assertSet('rawMax', 80);

        // Student 1 (Case 1): 8, 9, 7, 10, 30 -> raw 64 / 80 * 40 = 32.00
        $jPage->instance()->saveScore($jStudents[0]->id, $jH1->id, '8');
        $jPage->instance()->saveScore($jStudents[0]->id, $jH2->id, '9');
        $jPage->instance()->saveScore($jStudents[0]->id, $jH3->id, '7');
        $jPage->instance()->saveScore($jStudents[0]->id, $jH4->id, '10');
        $resJ1 = $jPage->instance()->saveScore($jStudents[0]->id, $jH5->id, '30');
        expect((float) $resJ1['raw_total'])->toBe(64.0)
            ->and((float) $resJ1['scaled_score'])->toBe(32.0)
            ->and($resJ1['student_grade'])->toBe('A');

        // Student 2 (Case 2): 9, 8.5, 9, 8, 30.5 -> raw 65 / 80 * 40 = 32.50
        $jPage->instance()->saveScore($jStudents[1]->id, $jH1->id, '9');
        $jPage->instance()->saveScore($jStudents[1]->id, $jH2->id, '8.5');
        $jPage->instance()->saveScore($jStudents[1]->id, $jH3->id, '9');
        $jPage->instance()->saveScore($jStudents[1]->id, $jH4->id, '8');
        $resJ2 = $jPage->instance()->saveScore($jStudents[1]->id, $jH5->id, '30.5');
        expect((float) $resJ2['raw_total'])->toBe(65.0)
            ->and((float) $resJ2['scaled_score'])->toBe(32.5)
            ->and($resJ2['student_grade'])->toBe('A');

        // Student 3 (ABS): absent on all heads
        foreach ([$jH1, $jH2, $jH3, $jH4, $jH5] as $h) {
            $jPage->instance()->saveScore($jStudents[2]->id, $h->id, '', true);
        }
        $resJ3 = MidtermSubjectResult::where('student_id', $jStudents[2]->id)->where('subject_id', $math->id)->first();
        expect($resJ3->status)->toBe('absent')
            ->and($resJ3->grade)->toBe('ABS')
            ->and($resJ3->scaled_score)->toBeNull();

        // Student 4 (Missing): unscored on head 5
        $jPage->instance()->saveScore($jStudents[3]->id, $jH1->id, '8');
        $jPage->instance()->saveScore($jStudents[3]->id, $jH2->id, '9');
        $jPage->instance()->saveScore($jStudents[3]->id, $jH3->id, '7');
        $jPage->instance()->saveScore($jStudents[3]->id, $jH4->id, '10');
        // head 5 is not entered -> status missing
        $resJ4 = MidtermSubjectResult::where('student_id', $jStudents[3]->id)->where('subject_id', $math->id)->first();
        expect($resJ4->status)->toBe('missing');

        // Verify Completeness Guard blocks locking when Student 4 is missing
        $jProcess = Livewire::actingAs($admin)
            ->test(ProcessMidtermResults::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $jss1->id);

        $jProcess->call('lockMidtermResults')
            ->assertNotified('Cannot Lock Results');

        // Now resolve Student 4 with score 30 on head 5
        $jPage->instance()->saveScore($jStudents[3]->id, $jH5->id, '30');
        $jProcess->call('calculateMidtermResults')
            ->assertNotified('Mid-term results calculated successfully!');

        $jProcess->call('lockMidtermResults')
            ->assertNotified('Mid-Term Results Locked');

        $jProcess->call('publishMidtermResults')
            ->assertNotified('Mid-Term Reports Published!');

        // ═════════════════════════════════════════════════════════════════════
        // STEP 4: SCORE ENTRY & REAL SCALING IN SS 1 (60 -> 30)
        // ═════════════════════════════════════════════════════════════════════
        Setting::updateOrCreate(['key' => 'midterm_absent_policy'], ['value' => 'excluded']);

        $sPage = Livewire::actingAs($sTeacher)
            ->test(EnterMidtermScores::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $ss1->id)
            ->set('subject_id', $math->id)
            ->assertSet('loaded', true)
            ->assertSet('midtermWeight', 30)
            ->assertSet('rawMax', 60);

        // Student 1 (Case 3): 5, 4, 8, 7, 22 -> raw 46 / 60 * 30 = 23.00
        $sPage->instance()->saveScore($sStudents[0]->id, $sH1->id, '5');
        $sPage->instance()->saveScore($sStudents[0]->id, $sH2->id, '4');
        $sPage->instance()->saveScore($sStudents[0]->id, $sH3->id, '8');
        $sPage->instance()->saveScore($sStudents[0]->id, $sH4->id, '7');
        $resS1 = $sPage->instance()->saveScore($sStudents[0]->id, $sH5->id, '22');
        expect((float) $resS1['raw_total'])->toBe(46.0)
            ->and((float) $resS1['scaled_score'])->toBe(23.0)
            ->and($resS1['student_grade'])->toBe('A');

        // Student 2 (Excluded Policy Student): with excluded policy, absent on sH3 (10)
        $sPage->instance()->saveScore($sStudents[1]->id, $sH1->id, '4');
        $sPage->instance()->saveScore($sStudents[1]->id, $sH2->id, '4');
        $sPage->instance()->saveScore($sStudents[1]->id, $sH3->id, '', true);
        $sPage->instance()->saveScore($sStudents[1]->id, $sH4->id, '8');
        $resS2 = $sPage->instance()->saveScore($sStudents[1]->id, $sH5->id, '24');
        // Raw max drops from 60 to 50! Raw sum = 40 / 50 * 30 = 24.00
        expect((float) $resS2['raw_total'])->toBe(40.0)
            ->and((float) $resS2['scaled_score'])->toBe(24.0)
            ->and($resS2['student_grade'])->toBe('A');

        // Reset policy to count_as_zero
        Setting::updateOrCreate(['key' => 'midterm_absent_policy'], ['value' => 'count_as_zero']);

        // Lock & Publish SS 1
        $sProcess = Livewire::actingAs($admin)
            ->test(ProcessMidtermResults::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $ss1->id);
        $sProcess->call('calculateMidtermResults');
        $sProcess->call('lockMidtermResults');
        $sProcess->call('publishMidtermResults');

        // ═════════════════════════════════════════════════════════════════════
        // STEP 5: ENTER EXAM SCORES
        // ═════════════════════════════════════════════════════════════════════
        // JSS 1: Exam Head (Max 60)
        $jExamPage = Livewire::actingAs($jTeacher)
            ->test(EnterScores::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $jss1->id)
            ->set('subject_id', $math->id);

        // JSS Student 1: Exam = 50 / 60 -> Midterm 32.0 + 50.0 = 82.00
        $jExamPage->instance()->saveScore($jStudents[0]->id, $jExam->id, '50');
        // JSS Student 2: Exam = 45 / 60 -> Midterm 32.5 + 45.0 = 77.50
        $jExamPage->instance()->saveScore($jStudents[1]->id, $jExam->id, '45');
        // JSS Student 3 (ABS): Exam = 55 / 60 -> Midterm ABS + 55.0 = 55.00
        $jExamPage->instance()->saveScore($jStudents[2]->id, $jExam->id, '55');
        // JSS Student 4: Exam = 48 / 60 -> Midterm 32.0 + 48.0 = 80.00
        $jExamPage->instance()->saveScore($jStudents[3]->id, $jExam->id, '48');

        // SS 1: Exam Head (Max 70)
        $sExamPage = Livewire::actingAs($sTeacher)
            ->test(EnterScores::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $ss1->id)
            ->set('subject_id', $math->id);

        // SS Student 1: Exam = 55 / 70 -> Midterm 23.0 + 55.0 = 78.00
        $sExamPage->instance()->saveScore($sStudents[0]->id, $sExam->id, '55');
        // SS Student 2: Exam = 50 / 70 -> Midterm 24.0 + 50.0 = 74.00
        $sExamPage->instance()->saveScore($sStudents[1]->id, $sExam->id, '50');

        // ═════════════════════════════════════════════════════════════════════
        // STEP 6: PROCESS FINAL CLASS RESULTS & ADMIN APPROVAL
        // ═════════════════════════════════════════════════════════════════════
        // Process JSS 1
        Livewire::actingAs($admin)
            ->test(ProcessClassResults::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $jss1->id)
            ->call('processClassResults')
            ->set('times_school_opened', 120)
            ->set('next_term_begins', '2027-01-15')
            ->call('approveClassResults')
            ->assertNotified('Class results approved and released to students!');

        // Verify JSS 1 Final Results
        // Student 1: 32.0 + 50.0 = 82.00 (Grade A)
        $srJ1 = SubjectResult::where('student_id', $jStudents[0]->id)->where('subject_id', $math->id)->first();
        expect((float) $srJ1->midterm_score)->toBe(32.0)
            ->and((float) $srJ1->exam_score)->toBe(50.0)
            ->and((float) $srJ1->total)->toBe(82.0)
            ->and($srJ1->grade)->toBe('A');

        // Student 2: 32.5 + 45.0 = 77.50 (Grade A)
        $srJ2 = SubjectResult::where('student_id', $jStudents[1]->id)->where('subject_id', $math->id)->first();
        expect((float) $srJ2->midterm_score)->toBe(32.5)
            ->and((float) $srJ2->exam_score)->toBe(45.0)
            ->and((float) $srJ2->total)->toBe(77.5)
            ->and($srJ2->grade)->toBe('A');

        // Student 3 (ABS): Midterm ABS + 55.0 = 55.00 (Grade C)
        $srJ3 = SubjectResult::where('student_id', $jStudents[2]->id)->where('subject_id', $math->id)->first();
        expect($srJ3->midterm_status)->toBe('absent')
            ->and((float) $srJ3->exam_score)->toBe(55.0)
            ->and((float) $srJ3->total)->toBe(55.0)
            ->and($srJ3->grade)->toBe('C');

        // Process SS 1
        Livewire::actingAs($admin)
            ->test(ProcessClassResults::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->set('classroom_id', $ss1->id)
            ->call('processClassResults')
            ->set('times_school_opened', 120)
            ->set('next_term_begins', '2027-01-15')
            ->call('approveClassResults')
            ->assertNotified('Class results approved and released to students!');

        // Verify SS 1 Final Results
        // Student 1: 23.0 + 55.0 = 78.00 (Grade A)
        $srS1 = SubjectResult::where('student_id', $sStudents[0]->id)->where('subject_id', $math->id)->first();
        expect((float) $srS1->midterm_score)->toBe(23.0)
            ->and((float) $srS1->exam_score)->toBe(55.0)
            ->and((float) $srS1->total)->toBe(78.0)
            ->and($srS1->grade)->toBe('A');

        // Student 2: 24.0 + 50.0 = 74.00 (Grade A)
        $srS2 = SubjectResult::where('student_id', $sStudents[1]->id)->where('subject_id', $math->id)->first();
        expect((float) $srS2->midterm_score)->toBe(24.0)
            ->and((float) $srS2->exam_score)->toBe(50.0)
            ->and((float) $srS2->total)->toBe(74.0)
            ->and($srS2->grade)->toBe('A');

        // ═════════════════════════════════════════════════════════════════════
        // STEP 7: STUDENT PORTAL VIEW & PDF DOWNLOADS (PIN UNLOCKED)
        // ═════════════════════════════════════════════════════════════════════
        $batch = ResultPinBatch::create(['batch_code' => 'B-FINAL-01', 'session_id' => $session->id, 'term_id' => $term->id, 'quantity' => 2]);
        ResultPin::create([
            'batch_id'      => $batch->id,
            'student_id'    => $jStudents[0]->id,
            'session_id'    => $session->id,
            'term_id'       => $term->id,
            'serial_number' => 'SN-FINAL-01',
            'pin'           => '999888',
            'is_used'       => true,
            'used_at'       => now(),
        ]);

        $this->actingAs($jStudents[0]->user);

        // Student Portal page asserts
        Livewire::test(StudentResultPage::class)
            ->set('session_id', $session->id)
            ->set('term_id', $term->id)
            ->assertSet('loaded', true)
            ->assertSet('isUnlocked', true)
            ->assertSee('82')
            ->assertSee('32')
            ->assertSee('50');

        // Download Midterm PDF
        $mPdf = $this->get(route('student.midterm-pdf', ['session_id' => $session->id, 'term_id' => $term->id]));
        $mPdf->assertOk()->assertHeader('content-type', 'application/pdf');

        // Download Terminal PDF
        $tPdf = $this->get(route('student.result-pdf', ['session_id' => $session->id, 'term_id' => $term->id]));
        $tPdf->assertOk()->assertHeader('content-type', 'application/pdf');
    });

});
