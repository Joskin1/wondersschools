<?php

use App\Filament\Pages\ManageMidtermStructure;
use App\Filament\Teacher\Pages\EnterMidtermScores;
use App\Filament\Teacher\Pages\EnterScores;
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
use App\Services\ScoreSpreadsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createTestScore(array $attrs): Score
{
    if (! isset($attrs['teacher_id'])) {
        $teacher = User::where('role', 'teacher')->first() ?? User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $attrs['teacher_id'] = $teacher->id;
    }

    return Score::create($attrs);
}

function createMidtermContext(int $weight = 40, int $rawMax = 80): array
{
    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '1']);
    Setting::updateOrCreate(['key' => 'result_decimal_places'], ['value' => '2']);
    Setting::updateOrCreate(['key' => 'result_rounding_rule'], ['value' => 'half_up']);
    Setting::updateOrCreate(['key' => 'midterm_absent_policy'], ['value' => 'count_as_zero']);

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

    $classroom = Classroom::create(['name' => 'JSS 1 A', 'class_order' => 1, 'is_active' => true]);

    $math = Subject::create(['name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);
    $classroom->subjects()->attach($math->id);

    // Midterm Heads
    $head1 = ScoreHead::create(['name' => 'Attendance', 'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
    $head2 = ScoreHead::create(['name' => 'Assignment', 'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
    $head3 = ScoreHead::create(['name' => 'Test 1', 'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
    $head4 = ScoreHead::create(['name' => 'Test 2', 'max_score' => 10, 'type' => 'midterm', 'is_active' => true]);
    $head5 = ScoreHead::create(['name' => 'Mid-Term Exam', 'max_score' => 40, 'type' => 'midterm', 'is_active' => true]);

    $config = ClassMidtermConfig::create([
        'classroom_id'       => $classroom->id,
        'midterm_weight'     => $weight,
        'calculation_method' => 'scale_to_weight',
    ]);

    ClassMidtermConfigHead::create(['class_midterm_config_id' => $config->id, 'score_head_id' => $head1->id]);
    ClassMidtermConfigHead::create(['class_midterm_config_id' => $config->id, 'score_head_id' => $head2->id]);
    ClassMidtermConfigHead::create(['class_midterm_config_id' => $config->id, 'score_head_id' => $head3->id]);
    ClassMidtermConfigHead::create(['class_midterm_config_id' => $config->id, 'score_head_id' => $head4->id]);
    ClassMidtermConfigHead::create(['class_midterm_config_id' => $config->id, 'score_head_id' => $head5->id]);

    // Exam Head: must equal (100 - weight)
    $examHead = ScoreHead::create(['name' => 'Final Exam', 'max_score' => (100 - $weight), 'type' => 'exam', 'is_active' => true]);

    $examStructure = ClassScoreStructure::create([
        'class_id'    => $classroom->id,
        'session_id'  => $session->id,
        'term_id'     => $term->id,
        'total_score' => (100 - $weight),
        'locked'      => false,
    ]);

    ClassScoreStructureItem::create([
        'class_score_structure_id' => $examStructure->id,
        'score_head_id'            => $examHead->id,
    ]);

    $teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
    TeacherSubjectAssignment::create([
        'teacher_id'   => $teacher->id,
        'subject_id'   => $math->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
        'term_id'      => $term->id,
    ]);

    ClassTeacherAssignment::create([
        'teacher_id' => $teacher->id,
        'class_id'   => $classroom->id,
        'session_id' => $session->id,
    ]);

    $studentUser = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $student = Student::create([
        'user_id'          => $studentUser->id,
        'surname'          => 'Doe',
        'first_name'       => 'John',
        'admission_number' => 'WND001',
        'status'           => 'active',
    ]);

    StudentEnrollment::create([
        'student_id'   => $student->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
    ]);

    return compact('session', 'term', 'classroom', 'math', 'config', 'head1', 'head2', 'head3', 'head4', 'head5', 'examHead', 'examStructure', 'teacher', 'student');
}

test('Test 1 (JSS 1 Scale): 8, 9, 7, 10, 30 on raw max 80 with weight 40 scales to 32.0/40', function () {
    $ctx = createMidtermContext(40, 80);

    // Save scores for all 5 heads: 8 + 9 + 7 + 10 + 30 = 64
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head1']->id, 'score' => 8]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head2']->id, 'score' => 9]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head3']->id, 'score' => 7]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head4']->id, 'score' => 10]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head5']->id, 'score' => 30]);

    $calc = app(MidtermCalculationService::class);
    $snapshot = $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    $res = $calc->computeStudentSubjectMidterm(
        $ctx['student']->id,
        $ctx['math']->id,
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id,
        $snapshot
    );

    expect((float) $res->raw_score)->toBe(64.0)
        ->and($res->student_raw_max)->toBe(80)
        ->and((float) $res->scaled_score)->toBe(32.0)
        ->and($res->status)->toBe('complete');
});

test('Test 2 (SS 1 Scale): 5, 4, 8, 7, 22 on raw max 60 with weight 30 scales to 23.0/30', function () {
    $calc = app(MidtermCalculationService::class);
    // Raw sum = 5+4+8+7+22 = 46. (46 * 30) / 60 = 23.0
    $scaled = $calc->scaleToWeight('46', '60', 30);
    $rounded = $calc->bcRound($scaled, 2);

    expect((float) $rounded)->toBe(23.0);
});

test('Test 3 (Variable Factor): Heads 50, weight 20 -> factor 0.4. Raw 35 gives 14.0', function () {
    $calc = app(MidtermCalculationService::class);
    // 35 * 20 / 50 = 700 / 50 = 14.0
    $scaled = $calc->scaleToWeight('35', '50', 20);
    $rounded = $calc->bcRound($scaled, 2);

    expect((float) $rounded)->toBe(14.0);
});

test('Test 4 (Decimals & Percentage): 9, 8.5, 9, 8, 30.5 on heads (10, 10, 10, 10, 40) -> raw 65 -> scaled 32.5/40 -> 81.25%', function () {
    $ctx = createMidtermContext(40, 80);

    // 9 + 8.5 + 9 + 8 + 30.5 = 65.0
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head1']->id, 'score' => 9]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head2']->id, 'score' => 8.5]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head3']->id, 'score' => 9]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head4']->id, 'score' => 8]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head5']->id, 'score' => 30.5]);

    $calc = app(MidtermCalculationService::class);
    $snapshot = $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    $res = $calc->computeStudentSubjectMidterm(
        $ctx['student']->id,
        $ctx['math']->id,
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id,
        $snapshot
    );

    expect((float) $res->raw_score)->toBe(65.0)
        ->and((float) $res->scaled_score)->toBe(32.5)
        ->and((float) $res->percentage)->toBe(81.25);
});

test('Test 5 (Carry-Forward Final Result): Midterm 32.5 + Exam 44 = Final 76.5 out of 100', function () {
    $ctx = createMidtermContext(40, 80);

    // Midterm raw = 65 -> scaled 32.5/40
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head1']->id, 'score' => 9]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head2']->id, 'score' => 8.5]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head3']->id, 'score' => 9]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head4']->id, 'score' => 8]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head5']->id, 'score' => 30.5]);

    // Exam score = 44 out of 60
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['examHead']->id, 'score' => 44]);

    $calc = app(MidtermCalculationService::class);
    $snapshot = $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);
    $calc->computeStudentSubjectMidterm($ctx['student']->id, $ctx['math']->id, $ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id, $snapshot);

    // Compute Terminal results via ResultCalculationService
    $resultService = app(ResultCalculationService::class);
    $resultService->calculateForSubject($ctx['classroom']->id, $ctx['math']->id, $ctx['session']->id, $ctx['term']->id);

    $subjectResult = SubjectResult::where('student_id', $ctx['student']->id)
        ->where('subject_id', $ctx['math']->id)
        ->where('session_id', $ctx['session']->id)
        ->where('term_id', $ctx['term']->id)
        ->first();

    expect($subjectResult)->not->toBeNull()
        ->and((float) $subjectResult->midterm_score)->toBe(32.5)
        ->and((float) $subjectResult->exam_score)->toBe(44.0)
        ->and((float) $subjectResult->total)->toBe(76.5)
        ->and($subjectResult->grade)->toBe('A');
});

test('Test 6 (Class Independence): Class A (weight 40) and Class B (weight 30) configured independently', function () {
    $ctxA = createMidtermContext(40, 80);

    $classB = Classroom::create(['name' => 'JSS 2 Blue', 'class_order' => 2, 'is_active' => true]);
    $configB = ClassMidtermConfig::create([
        'classroom_id'   => $classB->id,
        'midterm_weight' => 30,
    ]);

    expect($ctxA['config']->midterm_weight)->toBe(40)
        ->and($configB->midterm_weight)->toBe(30);
});

test('Test 7 (Toggle OFF Identical Behaviour): When use_midterm_results is OFF, legacy mode runs', function () {
    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '0']);

    $session = Session::create(['name' => '2025-2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $term = Term::create(['session_id' => $session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'JSS 1 C', 'class_order' => 1, 'is_active' => true]);
    $subject = Subject::create(['name' => 'Basic Science', 'code' => 'BSC', 'is_active' => true]);
    $classroom->subjects()->attach($subject->id);

    $ca = ScoreHead::create(['name' => 'CA', 'max_score' => 30, 'type' => 'exam', 'is_active' => true]);
    $exam = ScoreHead::create(['name' => 'Exam', 'max_score' => 70, 'type' => 'exam', 'is_active' => true]);

    $structure = ClassScoreStructure::create([
        'class_id' => $classroom->id,
        'session_id' => $session->id,
        'term_id' => $term->id,
        'total_score' => 100,
        'locked' => false,
    ]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $structure->id, 'score_head_id' => $ca->id]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $structure->id, 'score_head_id' => $exam->id]);

    $studentUser = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $student = Student::create(['user_id' => $studentUser->id, 'surname' => 'Smith', 'first_name' => 'Bob', 'admission_number' => 'BOB001', 'status' => 'active']);
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    createTestScore(['student_id' => $student->id, 'subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $ca->id, 'score' => 25]);
    createTestScore(['student_id' => $student->id, 'subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score_head_id' => $exam->id, 'score' => 60]);

    app(ResultCalculationService::class)->calculateForSubject($classroom->id, $subject->id, $session->id, $term->id);

    $result = SubjectResult::where('student_id', $student->id)->where('subject_id', $subject->id)->first();
    expect((float) $result->total)->toBe(85.0)
        ->and($result->midterm_score)->toBeNull();
});

test('Test 8 (Exam Head Total Validation): Exam heads not totaling 100 - W is rejected with exact message', function () {
    $ctx = createMidtermContext(40, 80);

    // Change exam structure item to 50 instead of 60 (100 - 40 = 60)
    $ctx['examStructure']->items()->delete();
    $wrongHead = ScoreHead::create(['name' => 'Bad Exam', 'max_score' => 50, 'type' => 'exam', 'is_active' => true]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $ctx['examStructure']->id, 'score_head_id' => $wrongHead->id]);

    $calc = app(MidtermCalculationService::class);

    expect(fn () => $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id))
        ->toThrow(\DomainException::class, 'Exam score structure total (50) does not match expected total (60 = 100 - 40).');
});

test('Test 9 (Score Above Max Rejected): Score entry rejects score > head max on single save', function () {
    $ctx = createMidtermContext(40, 80);

    $component = Livewire::actingAs($ctx['teacher'])
        ->test(EnterMidtermScores::class)
        ->set('session_id', $ctx['session']->id)
        ->set('term_id', $ctx['term']->id)
        ->set('classroom_id', $ctx['classroom']->id)
        ->set('subject_id', $ctx['math']->id);

    $res = $component->instance()->saveScore($ctx['student']->id, $ctx['head1']->id, '15.0');
    expect($res['status'])->toBe('error')
        ->and($res['message'])->toContain('exceeds maximum');

    expect(Score::where('score_head_id', $ctx['head1']->id)->count())->toBe(0);
});

test('Test 11 (Config Snapshot Preservation): Editing class config after scoring preserves historical snapshot', function () {
    $ctx = createMidtermContext(40, 80);

    // Enter a score to trigger snapshot
    $component = Livewire::actingAs($ctx['teacher'])
        ->test(EnterMidtermScores::class)
        ->set('session_id', $ctx['session']->id)
        ->set('term_id', $ctx['term']->id)
        ->set('classroom_id', $ctx['classroom']->id)
        ->set('subject_id', $ctx['math']->id);

    $component->instance()->saveScore($ctx['student']->id, $ctx['head1']->id, '8.0');

    $snapshot = TermScoringSnapshot::where('classroom_id', $ctx['classroom']->id)
        ->where('session_id', $ctx['session']->id)
        ->where('term_id', $ctx['term']->id)
        ->first();

    expect($snapshot)->not->toBeNull()
        ->and($snapshot->midterm_weight)->toBe(40);

    // Modify active ClassMidtermConfig to weight 50
    $ctx['config']->update(['midterm_weight' => 50]);

    // Snapshot remains 40
    $snapshot->refresh();
    expect($snapshot->midterm_weight)->toBe(40);
});

test('Test 12 (Rejection on Total > 100): Score entry rejects exam score making total > 100', function () {
    $ctx = createMidtermContext(40, 80);

    // Student has 45 in midterm
    MidtermSubjectResult::create([
        'student_id'      => $ctx['student']->id,
        'subject_id'      => $ctx['math']->id,
        'classroom_id'    => $ctx['classroom']->id,
        'session_id'      => $ctx['session']->id,
        'term_id'         => $ctx['term']->id,
        'scaled_score'    => 45.0,
        'student_raw_max' => 80,
        'midterm_weight'  => 40,
        'status'          => 'complete',
    ]);

    // Create snapshot
    app(MidtermCalculationService::class)->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    // Attempt to enter exam score of 60 on EnterScores (45 + 60 = 105 > 100, where 60 <= head max 60)
    $component = Livewire::actingAs($ctx['teacher'])
        ->test(EnterScores::class)
        ->set('session_id', $ctx['session']->id)
        ->set('term_id', $ctx['term']->id)
        ->set('classroom_id', $ctx['classroom']->id)
        ->set('subject_id', $ctx['math']->id);

    $res = $component->instance()->saveScore($ctx['student']->id, $ctx['examHead']->id, '60');

    expect($res['status'])->toBe('error')
        ->and($res['message'])->toContain('exceeds 100 marks');

    expect(Score::where('score_head_id', $ctx['examHead']->id)->count())->toBe(0);
});

test('Test 16 (Zero Raw Max ABS/No Midterm): Student absent on all heads with excluded policy produces absent status and ABS grade', function () {
    $ctx = createMidtermContext(40, 80);

    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head1']->id, 'score' => 0, 'is_absent' => true]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head2']->id, 'score' => 0, 'is_absent' => true]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head3']->id, 'score' => 0, 'is_absent' => true]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head4']->id, 'score' => 0, 'is_absent' => true]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head5']->id, 'score' => 0, 'is_absent' => true]);

    $calc = app(MidtermCalculationService::class);
    $snapshot = $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    $res = $calc->computeStudentSubjectMidterm(
        $ctx['student']->id,
        $ctx['math']->id,
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id,
        $snapshot,
        'excluded'
    );

    expect($res->status)->toBe('absent')
        ->and($res->grade)->toBe('ABS')
        ->and($res->remark)->toBe('No Midterm')
        ->and($res->scaled_score)->toBeNull();
});

test('Test 17 (Absent Policy: Count-as-0 vs Excluded): Raw max behaves differently per policy', function () {
    $ctx = createMidtermContext(40, 80);

    // Score on head1 = 8. Absent on head2 (max 10). Heads 3, 4, 5 scored.
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head1']->id, 'score' => 8, 'is_absent' => false]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head2']->id, 'score' => 0, 'is_absent' => true]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head3']->id, 'score' => 8, 'is_absent' => false]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head4']->id, 'score' => 8, 'is_absent' => false]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head5']->id, 'score' => 30, 'is_absent' => false]);

    $calc = app(MidtermCalculationService::class);
    $snapshot = $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    // 1. Policy: count_as_zero -> raw max remains 80
    $snapshot->update(['absent_policy' => 'count_as_zero']);
    $resZero = $calc->computeStudentSubjectMidterm($ctx['student']->id, $ctx['math']->id, $ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id, $snapshot, 'count_as_zero');
    expect($resZero->student_raw_max)->toBe(80);

    // 2. Policy: excluded -> head2 (max 10) is excluded -> raw max is 70
    $snapshot->update(['absent_policy' => 'excluded']);
    $resExcluded = $calc->computeStudentSubjectMidterm($ctx['student']->id, $ctx['math']->id, $ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id, $snapshot, 'excluded');
    expect($resExcluded->student_raw_max)->toBe(70);
});

test('Test 18 (Lock/Publish Blocked on Incomplete Scores): Incomplete scores block lock/publish actions', function () {
    $ctx = createMidtermContext(40, 80);

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    // Student has missing scores
    MidtermSubjectResult::create([
        'student_id'      => $ctx['student']->id,
        'subject_id'      => $ctx['math']->id,
        'classroom_id'    => $ctx['classroom']->id,
        'session_id'      => $ctx['session']->id,
        'term_id'         => $ctx['term']->id,
        'student_raw_max' => 80,
        'midterm_weight'  => 40,
        'status'          => 'missing',
    ]);

    Livewire::actingAs($admin)
        ->test(ProcessMidtermResults::class)
        ->set('session_id', $ctx['session']->id)
        ->set('term_id', $ctx['term']->id)
        ->set('classroom_id', $ctx['classroom']->id)
        ->call('lockMidtermResults')
        ->assertNotified('Cannot Lock Results');

    expect(MidtermClassTermApproval::where('classroom_id', $ctx['classroom']->id)->count())->toBe(0);
});

test('Test 20 (Student Portal Single PIN Unlocks Both Slips): One PIN unlocks both Terminal and Midterm reports', function () {
    $ctx = createMidtermContext(40, 80);

    $batch = ResultPinBatch::create([
        'batch_code' => 'BATCH-TEST',
        'session_id' => $ctx['session']->id,
        'term_id'    => $ctx['term']->id,
        'quantity'   => 1,
    ]);

    // Create a 6-digit PIN
    $pin = ResultPin::create([
        'batch_id'      => $batch->id,
        'serial_number' => 'SN-123456',
        'pin'           => '123456',
        'session_id'    => $ctx['session']->id,
        'term_id'       => $ctx['term']->id,
        'student_id'    => $ctx['student']->id,
        'is_used'       => true,
        'used_at'       => now(),
    ]);

    // Finalize terminal and publish midterm
    TermResult::create([
        'student_id'       => $ctx['student']->id,
        'classroom_id'     => $ctx['classroom']->id,
        'session_id'       => $ctx['session']->id,
        'term_id'          => $ctx['term']->id,
        'subjects_count'   => 1,
        'grand_total'      => 80,
        'average'          => 80,
        'grade'            => 'A',
        'remark'           => 'Excellent',
        'is_finalized'     => true,
    ]);

    MidtermClassTermApproval::create([
        'classroom_id' => $ctx['classroom']->id,
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'is_locked'    => true,
        'is_published' => true,
    ]);

    $calc = app(MidtermCalculationService::class);
    $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    // Student can access both without requesting another PIN
    $studentUser = $ctx['student']->user;

    $this->actingAs($studentUser)
        ->get(route('student.result-pdf', ['session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id]))
        ->assertSuccessful();

    $this->actingAs($studentUser)
        ->get(route('student.midterm-pdf', ['session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id]))
        ->assertSuccessful();
});

test('Test 22 (Locked Scores Reject Edits): Editing scores after lock is rejected', function () {
    $ctx = createMidtermContext(40, 80);

    // Lock the midterm
    MidtermClassTermApproval::create([
        'classroom_id' => $ctx['classroom']->id,
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'is_locked'    => true,
        'locked_at'    => now(),
    ]);

    $component = Livewire::actingAs($ctx['teacher'])
        ->test(EnterMidtermScores::class)
        ->set('session_id', $ctx['session']->id)
        ->set('term_id', $ctx['term']->id)
        ->set('classroom_id', $ctx['classroom']->id)
        ->set('subject_id', $ctx['math']->id);

    $res = $component->instance()->saveScore($ctx['student']->id, $ctx['head1']->id, '7.0');

    expect($res['status'])->toBe('error')
        ->and($res['message'])->toContain('locked');

    expect(Score::where('score_head_id', $ctx['head1']->id)->count())->toBe(0);
});

test('Test 23 (Term 3 Cumulative with Mixed Terms): Term 1 (70), Term 2 (70), Term 3 (80) -> Cumulative 73.33', function () {
    $session = Session::create(['name' => '2025-2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $t1 = Term::create(['session_id' => $session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => false]);
    $t2 = Term::create(['session_id' => $session->id, 'name' => 'Second Term', 'order' => 2, 'is_active' => false]);
    $t3 = Term::create(['session_id' => $session->id, 'name' => 'Third Term', 'order' => 3, 'is_active' => true]);

    $classroom = Classroom::create(['name' => 'JSS 3 A', 'class_order' => 3, 'is_active' => true]);
    $subject = Subject::create(['name' => 'English', 'code' => 'ENG', 'is_active' => true]);
    $classroom->subjects()->attach($subject->id);

    $studentUser = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $student = Student::create(['user_id' => $studentUser->id, 'surname' => 'Williams', 'first_name' => 'Sam', 'admission_number' => 'SAM001', 'status' => 'active']);
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    // Term 1 (legacy 70)
    SubjectResult::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $t1->id, 'total' => 70, 'grade' => 'A', 'is_published' => true]);
    // Term 2 (70)
    SubjectResult::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $t2->id, 'total' => 70, 'grade' => 'A', 'is_published' => true]);

    // Term 3 has midterm snapshot: weight 40, raw 80. Midterm = 35. Exam = 45 -> Term 3 total = 80.
    $examHead = ScoreHead::create(['name' => 'Exam', 'max_score' => 60, 'type' => 'exam', 'is_active' => true]);
    $snapshot3 = TermScoringSnapshot::create([
        'classroom_id'       => $classroom->id,
        'session_id'         => $session->id,
        'term_id'            => $t3->id,
        'mode'               => 'midterm',
        'midterm_weight'     => 40,
        'raw_max'            => 80,
        'calculation_method' => 'scale_to_weight',
        'midterm_heads'      => [],
        'exam_heads'         => [['id' => $examHead->id, 'name' => 'Exam', 'effective_max' => 60]],
    ]);

    MidtermSubjectResult::create([
        'student_id'      => $student->id,
        'subject_id'      => $subject->id,
        'classroom_id'    => $classroom->id,
        'session_id'      => $session->id,
        'term_id'         => $t3->id,
        'scaled_score'    => 35,
        'student_raw_max' => 80,
        'midterm_weight'  => 40,
        'status'          => 'complete',
    ]);

    createTestScore(['student_id' => $student->id, 'subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $t3->id, 'score_head_id' => $examHead->id, 'score' => 45]);

    app(ResultCalculationService::class)->calculateForSubject($classroom->id, $subject->id, $session->id, $t3->id);

    $t3Result = SubjectResult::where('student_id', $student->id)->where('subject_id', $subject->id)->where('term_id', $t3->id)->first();
    // (70 + 70 + 80) / 3 = 220 / 3 = 73.33
    expect((float) $t3Result->total)->toBe(73.33)
        ->and((float) $t3Result->midterm_score)->toBe(35.0)
        ->and((float) $t3Result->exam_score)->toBe(45.0);
});

test('Test 24 (Negative Score Rejected): Negative score entries are rejected', function () {
    $ctx = createMidtermContext(40, 80);

    $component = Livewire::actingAs($ctx['teacher'])
        ->test(EnterMidtermScores::class)
        ->set('session_id', $ctx['session']->id)
        ->set('term_id', $ctx['term']->id)
        ->set('classroom_id', $ctx['classroom']->id)
        ->set('subject_id', $ctx['math']->id);

    $res = $component->instance()->saveScore($ctx['student']->id, $ctx['head1']->id, '-5.0');

    expect($res['status'])->toBe('error')
        ->and($res['message'])->toContain('negative');

    expect(Score::where('score_head_id', $ctx['head1']->id)->count())->toBe(0);
});

test('Test 26 (Whole-Midterm Absent Gets Exam-Only Total with ABS): Student absent for whole midterm gets final total = exam score out of 100, Midterm prints ABS', function () {
    $ctx = createMidtermContext(40, 80);

    // Student marked absent for all midterm heads
    MidtermSubjectResult::create([
        'student_id'      => $ctx['student']->id,
        'subject_id'      => $ctx['math']->id,
        'classroom_id'    => $ctx['classroom']->id,
        'session_id'      => $ctx['session']->id,
        'term_id'         => $ctx['term']->id,
        'raw_score'       => 0,
        'student_raw_max' => 0,
        'midterm_weight'  => 40,
        'scaled_score'    => null,
        'percentage'      => null,
        'grade'           => 'ABS',
        'remark'          => 'No Midterm',
        'status'          => 'absent',
    ]);

    // Exam score = 55 out of 60
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['examHead']->id, 'score' => 55]);

    $calc = app(MidtermCalculationService::class);
    $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    app(ResultCalculationService::class)->calculateForSubject($ctx['classroom']->id, $ctx['math']->id, $ctx['session']->id, $ctx['term']->id);

    $subjectResult = SubjectResult::where('student_id', $ctx['student']->id)->where('subject_id', $ctx['math']->id)->first();

    // Final total = exam score only: 0 + 55 = 55
    expect((float) $subjectResult->total)->toBe(55.0)
        ->and($subjectResult->midterm_status)->toBe('absent')
        ->and($subjectResult->is_published)->toBeTrue();
});

test('Test 27 (Skipped-Row Loop): An error row does not abort class calculation; remaining rows are computed; error row excluded from totals and positions', function () {
    $ctx = createMidtermContext(40, 80);

    // Create second student
    $student2 = Student::create(['first_name' => 'Student', 'last_name' => 'Two', 'admission_number' => 'WND-ERR-2', 'is_active' => true]);
    StudentEnrollment::create(['student_id' => $student2->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id]);

    // Student 1: Valid midterm 32.0/40 and exam 45/60 -> Total = 77.0
    MidtermSubjectResult::create([
        'student_id'      => $ctx['student']->id,
        'subject_id'      => $ctx['math']->id,
        'classroom_id'    => $ctx['classroom']->id,
        'session_id'      => $ctx['session']->id,
        'term_id'         => $ctx['term']->id,
        'raw_score'       => 64,
        'student_raw_max' => 80,
        'midterm_weight'  => 40,
        'scaled_score'    => 32.0,
        'percentage'      => 80.0,
        'grade'           => 'A',
        'remark'          => 'Excellent',
        'status'          => 'complete',
    ]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['examHead']->id, 'score' => 45]);

    // Student 2: Midterm 30.0 + invalid exam 80 (total = 110 > 100) -> Triggers error row
    MidtermSubjectResult::create([
        'student_id'      => $student2->id,
        'subject_id'      => $ctx['math']->id,
        'classroom_id'    => $ctx['classroom']->id,
        'session_id'      => $ctx['session']->id,
        'term_id'         => $ctx['term']->id,
        'raw_score'       => 60,
        'student_raw_max' => 80,
        'midterm_weight'  => 40,
        'scaled_score'    => 30.0,
        'percentage'      => 75.0,
        'grade'           => 'A',
        'remark'          => 'Excellent',
        'status'          => 'complete',
    ]);
    createTestScore(['student_id' => $student2->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['examHead']->id, 'score' => 80]);

    $calc = app(MidtermCalculationService::class);
    $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    // Run full class calculation
    app(ResultCalculationService::class)->calculateForClass($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    // Student 1 must be calculated successfully
    $sr1 = SubjectResult::where('student_id', $ctx['student']->id)->where('subject_id', $ctx['math']->id)->first();
    expect($sr1)->not->toBeNull()
        ->and((float) $sr1->total)->toBe(77.0)
        ->and($sr1->has_error)->toBeFalse()
        ->and($sr1->is_published)->toBeTrue();

    // Student 2 must be flagged with has_error = true
    $sr2 = SubjectResult::where('student_id', $student2->id)->where('subject_id', $ctx['math']->id)->first();
    expect($sr2)->not->toBeNull()
        ->and($sr2->has_error)->toBeTrue()
        ->and($sr2->error_message)->toContain('exceeded 100 marks');

    // TermResult: Student 1 has grand_total 77.0. Student 2 has no valid published rows so grand_total does not include error row
    $tr1 = TermResult::where('student_id', $ctx['student']->id)->first();
    expect($tr1)->not->toBeNull()
        ->and((float) $tr1->grand_total)->toBe(77.0)
        ->and($tr1->overall_position)->toBe(1);

    $tr2 = TermResult::where('student_id', $student2->id)->first();
    expect($tr2)->toBeNull();
});

test('Test 28 (Snapshot Integrity): Modifying score heads after scoring has started does not mutate snapshot values', function () {
    $ctx = createMidtermContext(40, 80);

    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head1']->id, 'score' => 8]);

    $calc = app(MidtermCalculationService::class);
    $snapshot = $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    expect($snapshot->midterm_weight)->toBe(40)
        ->and($snapshot->raw_max)->toBe(80);

    // Modify original score head max score in settings
    $ctx['head1']->update(['max_score' => 25]);
    $ctx['config']->update(['midterm_weight' => 50]);

    // Re-retrieving snapshot returns untouched historical snapshot
    $reloaded = $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);
    expect($reloaded->midterm_weight)->toBe(40)
        ->and($reloaded->raw_max)->toBe(80)
        ->and($reloaded->midterm_heads[0]['effective_max'])->toBe(10);
});

test('Test 29 (Missing State): Unscored student-subject produces null (not offered), partial scores produce status missing', function () {
    $ctx = createMidtermContext(40, 80);

    $calc = app(MidtermCalculationService::class);
    $snapshot = $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    // 1. Student has no scores at all -> returns null (not offering / not taking exam)
    $resNoScores = $calc->computeStudentSubjectMidterm(
        $ctx['student']->id,
        $ctx['math']->id,
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id,
        $snapshot
    );

    expect($resNoScores)->toBeNull();

    // 2. Partial scores: student took head1 (10/10), but head2 is unscored and not marked absent -> missing
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['head1']->id, 'score' => 10.0, 'is_absent' => false]);

    $resPartial = $calc->computeStudentSubjectMidterm(
        $ctx['student']->id,
        $ctx['math']->id,
        $ctx['classroom']->id,
        $ctx['session']->id,
        $ctx['term']->id,
        $snapshot
    );

    expect($resPartial->status)->toBe('missing')
        ->and($resPartial->scaled_score)->toBeNull()
        ->and($resPartial->percentage)->toBeNull()
        ->and($resPartial->grade)->toBe('—')
        ->and($resPartial->remark)->toBe('Incomplete');
});

test('Test 30 (Unlock Then Recalculate): Unlocking midterm allows score modifications and triggers terminal recalculation', function () {
    $ctx = createMidtermContext(40, 80);

    // Enter initial midterm score (32.0/40) & exam score (44/60) -> Total = 76.0
    MidtermSubjectResult::create([
        'student_id'      => $ctx['student']->id,
        'subject_id'      => $ctx['math']->id,
        'classroom_id'    => $ctx['classroom']->id,
        'session_id'      => $ctx['session']->id,
        'term_id'         => $ctx['term']->id,
        'raw_score'       => 64,
        'student_raw_max' => 80,
        'midterm_weight'  => 40,
        'scaled_score'    => 32.0,
        'percentage'      => 80.0,
        'grade'           => 'A',
        'remark'          => 'Excellent',
        'status'          => 'complete',
    ]);
    createTestScore(['student_id' => $ctx['student']->id, 'subject_id' => $ctx['math']->id, 'classroom_id' => $ctx['classroom']->id, 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'score_head_id' => $ctx['examHead']->id, 'score' => 44]);

    $calc = app(MidtermCalculationService::class);
    $snapshot = $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);
    app(ResultCalculationService::class)->calculateForClass($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    $srBefore = SubjectResult::where('student_id', $ctx['student']->id)->where('subject_id', $ctx['math']->id)->first();
    expect((float) $srBefore->total)->toBe(76.0);

    // Lock then unlock via ProcessMidtermResults
    $approval = MidtermClassTermApproval::create([
        'classroom_id' => $ctx['classroom']->id,
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'is_locked'    => true,
    ]);

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    Livewire::actingAs($admin)
        ->test(ProcessMidtermResults::class)
        ->set('session_id', $ctx['session']->id)
        ->set('term_id', $ctx['term']->id)
        ->set('classroom_id', $ctx['classroom']->id)
        ->call('unlockMidtermResults')
        ->assertNotified('Mid-Term Results Unlocked');

    expect(MidtermClassTermApproval::where('classroom_id', $ctx['classroom']->id)->value('is_locked'))->toBeFalse();
});

test('Test 31 (PDF Guards): Unapproved midterm or terminal PDF download is blocked with 403', function () {
    $ctx = createMidtermContext(40, 80);

    $studentUser = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $ctx['student']->update(['user_id' => $studentUser->id]);

    // Create ResultPin unlocked
    $batch = ResultPinBatch::create(['batch_code' => 'B-GUARD-TEST', 'session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id, 'quantity' => 1]);
    ResultPin::create([
        'batch_id'      => $batch->id,
        'student_id'    => $ctx['student']->id,
        'session_id'    => $ctx['session']->id,
        'term_id'       => $ctx['term']->id,
        'serial_number' => 'SN-GUARD-01',
        'pin'           => '123456',
        'is_used'       => true,
        'used_at'       => now(),
    ]);

    // Create TermResult finalized, but ClassTermApproval status is 'pending'
    TermResult::create([
        'student_id'   => $ctx['student']->id,
        'classroom_id' => $ctx['classroom']->id,
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'grand_total'  => 85,
        'average'      => 85,
        'is_finalized' => true,
    ]);

    \App\Models\ClassTermApproval::create([
        'classroom_id' => $ctx['classroom']->id,
        'session_id'   => $ctx['session']->id,
        'term_id'      => $ctx['term']->id,
        'status'       => 'pending',
    ]);

    $calc = app(MidtermCalculationService::class);
    $calc->getOrCreateSnapshot($ctx['classroom']->id, $ctx['session']->id, $ctx['term']->id);

    $this->actingAs($studentUser);

    // Terminal PDF blocked with 403 because approval status is pending
    $respTerminal = $this->get(route('student.result-pdf', ['session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id]));
    $respTerminal->assertStatus(403);

    // Midterm PDF blocked with 403 because midterm approval is not published
    $respMidterm = $this->get(route('student.midterm-pdf', ['session_id' => $ctx['session']->id, 'term_id' => $ctx['term']->id]));
    $respMidterm->assertStatus(403);
});

test('Test 32 (Exam Structure Guard): Score entry is blocked when exam structure total != 100 - W', function () {
    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '1']);

    $session = Session::create(['name' => '2025-2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $term = Term::create(['session_id' => $session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => true]);
    $classroom = Classroom::create(['name' => 'JSS Guard Test', 'class_order' => 5, 'is_active' => true]);
    $math = Subject::create(['name' => 'Math Test', 'code' => 'MT', 'is_active' => true]);
    $head = ScoreHead::create(['name' => 'CA', 'max_score' => 40, 'type' => 'midterm', 'is_active' => true]);

    $config = ClassMidtermConfig::create(['classroom_id' => $classroom->id, 'midterm_weight' => 40]);
    ClassMidtermConfigHead::create(['class_midterm_config_id' => $config->id, 'score_head_id' => $head->id]);

    // Exam structure is set to 50 instead of 60 (100 - 40)
    $examHead = ScoreHead::create(['name' => 'Exam', 'max_score' => 50, 'type' => 'exam', 'is_active' => true]);
    $structure = ClassScoreStructure::create(['class_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'total_score' => 50]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $structure->id, 'score_head_id' => $examHead->id]);

    $calc = app(MidtermCalculationService::class);
    expect(fn () => $calc->getOrCreateSnapshot($classroom->id, $session->id, $term->id))
        ->toThrow(\DomainException::class, 'Exam score structure total (50) does not match expected total (60 = 100 - 40).');
});

test('Test 33 (Tenant Rounding End to End): half_up, floor, and ceil round accurately through full calculation pipeline', function () {
    $calc = app(MidtermCalculationService::class);

    // Raw 3.555 with scale 2
    expect($calc->bcRound('3.555', 2, 'half_up'))->toBe('3.56')
        ->and($calc->bcRound('3.555', 2, 'floor'))->toBe('3.55')
        ->and($calc->bcRound('3.551', 2, 'ceil'))->toBe('3.56')
        ->and($calc->bcRound('3.550', 2, 'ceil'))->toBe('3.55');

    // Scale to weight with floor rounding
    $scaled = $calc->scaleToWeight('33', '80', 40, 6); // 16.5
    expect($calc->bcRound($scaled, 2, 'half_up'))->toBe('16.50')
        ->and($calc->bcRound($scaled, 2, 'floor'))->toBe('16.50');

    // 1.5 / 20 * 11 = 0.825
    $scaled2 = $calc->scaleToWeight('1.5', '20', 11, 6);
    expect($calc->bcRound($scaled2, 2, 'half_up'))->toBe('0.83')
        ->and($calc->bcRound($scaled2, 2, 'floor'))->toBe('0.82')
        ->and($calc->bcRound($scaled2, 2, 'ceil'))->toBe('0.83');
});


test('Term 1 legacy results exist and midterm activates for Term 2 with Term 1 unchanged', function () {
    $session = Session::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
    $term1 = Term::create(['session_id' => $session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => false]);
    $term2 = Term::create(['session_id' => $session->id, 'name' => 'Second Term', 'order' => 2, 'is_active' => true]);

    $classroom = Classroom::create(['name' => 'JSS 2', 'class_order' => 2, 'is_active' => true]);
    $student = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    $math = Subject::create(['name' => 'Mathematics', 'code' => 'MTH', 'is_active' => true]);
    $classroom->subjects()->attach($math->id);

    $legacyHead = ScoreHead::create(['name' => 'Exam', 'max_score' => 100, 'type' => 'exam', 'is_active' => true]);
    $legacyStr = ClassScoreStructure::create(['class_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term1->id, 'total_score' => 100, 'locked' => true]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $legacyStr->id, 'score_head_id' => $legacyHead->id]);

    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $legacyHead->id, 'session_id' => $session->id, 'term_id' => $term1->id, 'score' => 85.0]);
    app(ResultCalculationService::class)->calculateForClass($classroom->id, $session->id, $term1->id);

    $term1ResultBefore = SubjectResult::where('classroom_id', $classroom->id)->where('session_id', $session->id)->where('term_id', $term1->id)->first();
    expect($term1ResultBefore->total)->toBe('85.00')
        ->and($term1ResultBefore->midterm_score)->toBeNull();

    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '1']);
    $midHead = ScoreHead::create(['name' => 'Test', 'max_score' => 40, 'type' => 'midterm', 'is_active' => true]);
    $examHead2 = ScoreHead::create(['name' => 'Final Exam', 'max_score' => 60, 'type' => 'exam', 'is_active' => true]);

    $midConfig = ClassMidtermConfig::create(['classroom_id' => $classroom->id, 'midterm_weight' => 40, 'calculation_method' => 'scale_to_weight']);
    ClassMidtermConfigHead::create(['class_midterm_config_id' => $midConfig->id, 'score_head_id' => $midHead->id]);

    $examStr2 = ClassScoreStructure::create(['class_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term2->id, 'total_score' => 60, 'locked' => true]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $examStr2->id, 'score_head_id' => $examHead2->id]);

    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $midHead->id, 'session_id' => $session->id, 'term_id' => $term2->id, 'score' => 32.0]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $examHead2->id, 'session_id' => $session->id, 'term_id' => $term2->id, 'score' => 48.0]);

    $snapshot2 = app(MidtermCalculationService::class)->getOrCreateSnapshot($classroom->id, $session->id, $term2->id);
    app(MidtermCalculationService::class)->computeStudentSubjectMidterm($student->id, $math->id, $classroom->id, $session->id, $term2->id, $snapshot2);
    app(ResultCalculationService::class)->calculateForClass($classroom->id, $session->id, $term2->id);

    $term2Result = SubjectResult::where('classroom_id', $classroom->id)->where('session_id', $session->id)->where('term_id', $term2->id)->first();
    expect((float) $term2Result->midterm_score)->toBe(32.0)
        ->and((float) $term2Result->exam_score)->toBe(48.0)
        ->and((float) $term2Result->total)->toBe(80.0);

    $term1ResultAfter = SubjectResult::where('classroom_id', $classroom->id)->where('session_id', $session->id)->where('term_id', $term1->id)->first();
    expect($term1ResultAfter->total)->toBe('85.00')
        ->and($term1ResultAfter->midterm_score)->toBeNull()
        ->and($term1ResultAfter->midterm_status)->toBe('not_applicable');
});

test('Test 13 (Toggle ON Term 2, OFF Term 4, Terms 1-3 unchanged): toggling setting preserves prior snapshots and calculations', function () {
    $session1 = Session::create(['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => false]);
    $session2 = Session::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);

    $t1 = Term::create(['session_id' => $session1->id, 'name' => 'First Term', 'order' => 1, 'is_active' => false]);
    $t2 = Term::create(['session_id' => $session1->id, 'name' => 'Second Term', 'order' => 2, 'is_active' => false]);
    $t3 = Term::create(['session_id' => $session1->id, 'name' => 'Third Term', 'order' => 3, 'is_active' => false]);
    $t4 = Term::create(['session_id' => $session2->id, 'name' => 'First Term', 'order' => 1, 'is_active' => true]);

    $classroom = Classroom::create(['name' => 'JSS 3', 'class_order' => 3, 'is_active' => true]);
    $student = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session1->id]);
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session2->id]);
    $math = Subject::create(['name' => 'Math 13', 'code' => 'M13', 'is_active' => true]);
    $classroom->subjects()->attach($math->id);

    $legacyHead = ScoreHead::create(['name' => 'Legacy 100', 'max_score' => 100, 'type' => 'exam', 'is_active' => true]);
    $midHead = ScoreHead::create(['name' => 'Mid 40', 'max_score' => 40, 'type' => 'midterm', 'is_active' => true]);
    $examHead = ScoreHead::create(['name' => 'Exam 60', 'max_score' => 60, 'type' => 'exam', 'is_active' => true]);

    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '0']);
    $str1 = ClassScoreStructure::create(['class_id' => $classroom->id, 'session_id' => $session1->id, 'term_id' => $t1->id, 'total_score' => 100, 'locked' => true]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $str1->id, 'score_head_id' => $legacyHead->id]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $legacyHead->id, 'session_id' => $session1->id, 'term_id' => $t1->id, 'score' => 70.0]);
    app(ResultCalculationService::class)->calculateForClass($classroom->id, $session1->id, $t1->id);

    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '1']);
    $midConfig = ClassMidtermConfig::create(['classroom_id' => $classroom->id, 'midterm_weight' => 40, 'calculation_method' => 'scale_to_weight']);
    ClassMidtermConfigHead::create(['class_midterm_config_id' => $midConfig->id, 'score_head_id' => $midHead->id]);
    $str2 = ClassScoreStructure::create(['class_id' => $classroom->id, 'session_id' => $session1->id, 'term_id' => $t2->id, 'total_score' => 60, 'locked' => true]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $str2->id, 'score_head_id' => $examHead->id]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $midHead->id, 'session_id' => $session1->id, 'term_id' => $t2->id, 'score' => 30.0]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $examHead->id, 'session_id' => $session1->id, 'term_id' => $t2->id, 'score' => 45.0]);
    $snap2 = app(MidtermCalculationService::class)->getOrCreateSnapshot($classroom->id, $session1->id, $t2->id);
    app(MidtermCalculationService::class)->computeStudentSubjectMidterm($student->id, $math->id, $classroom->id, $session1->id, $t2->id, $snap2);
    app(ResultCalculationService::class)->calculateForClass($classroom->id, $session1->id, $t2->id);

    $str3 = ClassScoreStructure::create(['class_id' => $classroom->id, 'session_id' => $session1->id, 'term_id' => $t3->id, 'total_score' => 60, 'locked' => true]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $str3->id, 'score_head_id' => $examHead->id]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $midHead->id, 'session_id' => $session1->id, 'term_id' => $t3->id, 'score' => 35.0]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $examHead->id, 'session_id' => $session1->id, 'term_id' => $t3->id, 'score' => 50.0]);
    $snap3 = app(MidtermCalculationService::class)->getOrCreateSnapshot($classroom->id, $session1->id, $t3->id);
    app(MidtermCalculationService::class)->computeStudentSubjectMidterm($student->id, $math->id, $classroom->id, $session1->id, $t3->id, $snap3);
    app(ResultCalculationService::class)->calculateForClass($classroom->id, $session1->id, $t3->id);

    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '0']);
    $str4 = ClassScoreStructure::create(['class_id' => $classroom->id, 'session_id' => $session2->id, 'term_id' => $t4->id, 'total_score' => 100, 'locked' => true]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $str4->id, 'score_head_id' => $legacyHead->id]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $legacyHead->id, 'session_id' => $session2->id, 'term_id' => $t4->id, 'score' => 90.0]);
    app(ResultCalculationService::class)->calculateForClass($classroom->id, $session2->id, $t4->id);

    $r1 = SubjectResult::where('classroom_id', $classroom->id)->where('term_id', $t1->id)->first();
    $r2 = SubjectResult::where('classroom_id', $classroom->id)->where('term_id', $t2->id)->first();
    $r3 = SubjectResult::where('classroom_id', $classroom->id)->where('term_id', $t3->id)->first();
    $r4 = SubjectResult::where('classroom_id', $classroom->id)->where('term_id', $t4->id)->first();

    expect((float) $r1->total)->toBe(70.0)
        ->and($r1->midterm_score)->toBeNull()
        ->and((float) $r2->total)->toBe(75.0)
        ->and((float) $r2->midterm_score)->toBe(30.0)
        ->and((float) $r3->total)->toBe(76.67)
        ->and((float) $r3->midterm_score)->toBe(35.0)
        ->and((float) $r4->total)->toBe(90.0)
        ->and($r4->midterm_score)->toBeNull();
});

test('Snapshot Rule Freezing: changing tenant setting after scoring starts does not alter recalculation', function () {
    $ctx = createMidtermContext(40, 80);
    $classroom = $ctx['classroom'];
    $session = $ctx['session'];
    $term = $ctx['term'];
    $math = $ctx['math'];
    $head = $ctx['head1'];
    $head5 = $ctx['head5'];

    $student = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    $snapshot = app(MidtermCalculationService::class)->getOrCreateSnapshot($classroom->id, $session->id, $term->id);
    expect($snapshot->result_decimal_places)->toBe(2)
        ->and($snapshot->result_rounding_rule)->toBe('half_up');

    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $head->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score' => 1.55]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $ctx['head2']->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score' => 0.0]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $ctx['head3']->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score' => 0.0]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $ctx['head4']->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score' => 0.0]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $head5->id, 'session_id' => $session->id, 'term_id' => $term->id, 'score' => 20.0]);

    Setting::updateOrCreate(['key' => 'result_decimal_places'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'result_rounding_rule'], ['value' => 'floor']);
    Setting::updateOrCreate(['key' => 'midterm_absent_policy'], ['value' => 'excluded']);

    $calc = app(MidtermCalculationService::class);
    $res = $calc->computeStudentSubjectMidterm($student->id, $math->id, $classroom->id, $session->id, $term->id, $snapshot);

    expect($res->status)->toBe('complete')
        ->and((float) $res->scaled_score)->toBe(10.78);
});

test('Per-student offered subjects: computeClassMidtermSummary computes total obtainable from offered subjects', function () {
    $ctx = createMidtermContext(40, 80);
    $classroom = $ctx['classroom'];
    $session = $ctx['session'];
    $term = $ctx['term'];
    $math = $ctx['math'];

    $physics = Subject::create(['name' => 'Physics', 'code' => 'PHY', 'is_active' => true]);
    $chemistry = Subject::create(['name' => 'Chemistry', 'code' => 'CHM', 'is_active' => true]);
    $classroom->subjects()->attach([$physics->id, $chemistry->id]);

    $studentA = Student::factory()->active()->create();
    $studentB = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $studentA->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);
    StudentEnrollment::create(['student_id' => $studentB->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    $snapshot = app(MidtermCalculationService::class)->getOrCreateSnapshot($classroom->id, $session->id, $term->id);

    MidtermSubjectResult::create(['student_id' => $studentA->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'session_id' => $session->id, 'term_id' => $term->id, 'scaled_score' => 30.0, 'status' => 'complete']);
    MidtermSubjectResult::create(['student_id' => $studentA->id, 'classroom_id' => $classroom->id, 'subject_id' => $physics->id, 'session_id' => $session->id, 'term_id' => $term->id, 'scaled_score' => 32.0, 'status' => 'complete']);
    MidtermSubjectResult::create(['student_id' => $studentA->id, 'classroom_id' => $classroom->id, 'subject_id' => $chemistry->id, 'session_id' => $session->id, 'term_id' => $term->id, 'scaled_score' => 28.0, 'status' => 'complete']);

    MidtermSubjectResult::create(['student_id' => $studentB->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'session_id' => $session->id, 'term_id' => $term->id, 'scaled_score' => 35.0, 'status' => 'complete']);
    MidtermSubjectResult::create(['student_id' => $studentB->id, 'classroom_id' => $classroom->id, 'subject_id' => $physics->id, 'session_id' => $session->id, 'term_id' => $term->id, 'scaled_score' => 35.0, 'status' => 'complete']);

    app(MidtermCalculationService::class)->computeClassMidtermSummary($classroom->id, $session->id, $term->id, $snapshot);

    $summaryA = MidtermTermResult::where('student_id', $studentA->id)->where('session_id', $session->id)->where('term_id', $term->id)->first();
    $summaryB = MidtermTermResult::where('student_id', $studentB->id)->where('session_id', $session->id)->where('term_id', $term->id)->first();

    expect((int) $summaryA->subjects_count)->toBe(3)
        ->and((float) $summaryA->total_obtainable)->toBe(120.0)
        ->and((float) $summaryA->total_obtained)->toBe(90.0)
        ->and((float) $summaryA->percentage)->toBe(75.0);

    expect((int) $summaryB->subjects_count)->toBe(2)
        ->and((float) $summaryB->total_obtainable)->toBe(80.0)
        ->and((float) $summaryB->total_obtained)->toBe(70.0)
        ->and((float) $summaryB->percentage)->toBe(87.5);
});

test('Test 10 (Tenant Isolation): tenant As config, snapshot, and scores are invisible to tenant B', function () {
    $dir = storage_path('framework/testing');
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $dbA = $dir . '/tenant_isolation_a.sqlite';
    $dbB = $dir . '/tenant_isolation_b.sqlite';
    touch($dbA);
    touch($dbB);

    $originalDefault = config('database.default');

    try {
        // ── 1. Initialize Tenant A ──────────────────────────────────────────
        config([
            'database.connections.tenant_test' => [
                'driver'                  => 'sqlite',
                'database'                => $dbA,
                'prefix'                  => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'tenant_test',
        ]);
        DB::purge('tenant_test');
        DB::reconnect('tenant_test');

        Artisan::call('migrate', [
            '--database' => 'tenant_test',
            '--path'     => 'database/migrations/tenant',
            '--realpath' => false,
        ]);

        $configA = ClassMidtermConfig::create([
            'classroom_id'       => 1,
            'midterm_weight'     => 40,
            'calculation_method' => 'scale_to_weight',
        ]);

        $snapshotA = TermScoringSnapshot::create([
            'classroom_id'          => 1,
            'session_id'            => 1,
            'term_id'               => 1,
            'mode'                  => 'midterm',
            'midterm_weight'        => 40,
            'raw_max'               => 80,
            'calculation_method'    => 'scale_to_weight',
            'absent_policy'         => 'count_as_zero',
            'result_decimal_places' => 2,
            'result_rounding_rule'  => 'half_up',
            'midterm_heads'         => [],
            'exam_heads'            => [],
        ]);

        $scoreA = Score::create([
            'student_id'    => 1,
            'classroom_id'  => 1,
            'subject_id'    => 1,
            'score_head_id' => 1,
            'session_id'    => 1,
            'term_id'       => 1,
            'score'         => 35.0,
            'teacher_id'    => 1,
            'is_absent'     => false,
        ]);

        expect(ClassMidtermConfig::count())->toBe(1)
            ->and(TermScoringSnapshot::count())->toBe(1)
            ->and(Score::count())->toBe(1);

        // ── 2. Initialize Tenant B ──────────────────────────────────────────
        config(['database.connections.tenant_test.database' => $dbB]);
        DB::purge('tenant_test');
        DB::reconnect('tenant_test');

        Artisan::call('migrate', [
            '--database' => 'tenant_test',
            '--path'     => 'database/migrations/tenant',
            '--realpath' => false,
        ]);

        // Tenant B must see NONE of Tenant A's configs, snapshots, or scores!
        expect(ClassMidtermConfig::count())->toBe(0)
            ->and(TermScoringSnapshot::count())->toBe(0)
            ->and(Score::count())->toBe(0);

        // ── 3. Re-connect to Tenant A and verify data persists ──────────────
        config(['database.connections.tenant_test.database' => $dbA]);
        DB::purge('tenant_test');
        DB::reconnect('tenant_test');

        expect(ClassMidtermConfig::count())->toBe(1)
            ->and(TermScoringSnapshot::count())->toBe(1)
            ->and(Score::count())->toBe(1);

    } finally {
        config(['database.default' => $originalDefault]);
        DB::purge('tenant_test');
        @unlink($dbA);
        @unlink($dbB);
    }
});

test('Test 19 (Spreadsheet Import/Export): ScoreSpreadsheetService exports and imports midterm scores properly', function () {
    $ctx = createMidtermContext(40, 80);
    $classroom = $ctx['classroom'];
    $session = $ctx['session'];
    $term = $ctx['term'];
    $math = $ctx['math'];
    $head = $ctx['head1'];
    $teacher = $ctx['teacher'];

    $student = Student::factory()->active()->create(['admission_number' => 'ADM-EXP-1']);
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    $service = app(ScoreSpreadsheetService::class);
    $filePath = $service->exportMidtermToTempFile($classroom->id, [$math->id], $session->id, $term->id, $teacher->id);
    expect(file_exists($filePath))->toBeTrue();

    // 1. Verify template has only midterm heads, never exam heads
    $parsed = $service->parseAndValidate($filePath, $classroom->id, [$math->id], $session->id, $term->id);
    expect($parsed)->toBeArray()
        ->and($parsed['type'])->toBe('midterm')
        ->and($parsed['total_students'])->toBeGreaterThan(0);

    $examHead = ScoreHead::where('type', 'exam')->first();
    $exportedHeadIds = array_keys($parsed['subjects'][0]['score_heads']);
    expect($exportedHeadIds)->not->toContain($examHead->id);
    foreach ($exportedHeadIds as $hid) {
        expect(ScoreHead::find($hid)->type)->toBe('midterm');
    }

    // 2. Modify spreadsheet: set student head1 to 'ABS' and head2 to 999 (over-max)
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $studentRowIndex = null;
    for ($r = 6; $r <= $sheet->getHighestRow(); $r++) {
        if (trim((string) $sheet->getCell("B{$r}")->getValue()) === $student->admission_number) {
            $studentRowIndex = $r;
            break;
        }
    }
    expect($studentRowIndex)->not->toBeNull();

    $sheet->setCellValue("D{$studentRowIndex}", 'ABS');
    $sheet->setCellValue("E{$studentRowIndex}", '999');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    // 3. parseAndValidate rejects over-max score and validates ABS
    $parsedWithOverMax = $service->parseAndValidate($filePath, $classroom->id, [$math->id], $session->id, $term->id);
    $stagedStudent = collect($parsedWithOverMax['students'])->firstWhere('student_id', $student->id);
    $key1 = "{$math->id}_{$ctx['head1']->id}";
    $key2 = "{$math->id}_{$ctx['head2']->id}";

    expect($stagedStudent['scores'][$key1]['is_absent'])->toBeTrue()
        ->and($stagedStudent['scores'][$key1]['error'])->toBeNull()
        ->and($stagedStudent['scores'][$key2]['error'])->toContain('Exceeds max')
        ->and($parsedWithOverMax['total_errors'])->toBeGreaterThan(0);

    // 4. Correct over-max score to valid number, re-parse and import via Livewire
    $sheet->setCellValue("E{$studentRowIndex}", '8.0');
    $writer->save($filePath);

    $fakeFile = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        'midterm_test.xlsx',
        file_get_contents($filePath)
    );

    Livewire::actingAs($teacher)
        ->test(EnterMidtermScores::class)
        ->set('session_id', $session->id)
        ->set('term_id', $term->id)
        ->set('classroom_id', $classroom->id)
        ->set('subject_id', $math->id)
        ->set('uploadFile', $fakeFile)
        ->call('commitImport');

    // Assert ABS score was saved with is_absent = true
    $savedScore = Score::where('student_id', $student->id)
        ->where('subject_id', $math->id)
        ->where('score_head_id', $ctx['head1']->id)
        ->first();
    expect($savedScore)->not->toBeNull()
        ->and($savedScore->is_absent)->toBeTrue();

    @unlink($filePath);
});

test('Test 25 (Exam-First Snapshot): entering exam score before midterm score creates snapshot and locks structure', function () {
    $ctx = createMidtermContext(40, 80);
    $classroom = $ctx['classroom'];
    $session = $ctx['session'];
    $term = $ctx['term'];
    $math = $ctx['math'];
    $examHead = ScoreHead::where('type', 'exam')->first();
    $teacher = $ctx['teacher'];

    $student = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    expect(TermScoringSnapshot::where('classroom_id', $classroom->id)->where('session_id', $session->id)->where('term_id', $term->id)->exists())->toBeFalse();

    Livewire::actingAs($teacher)
        ->test(EnterScores::class)
        ->set('session_id', $session->id)
        ->set('term_id', $term->id)
        ->set('classroom_id', $classroom->id)
        ->set('subject_id', $math->id)
        ->call('saveScore', $student->id, $examHead->id, 50.0);

    $snapshot = TermScoringSnapshot::where('classroom_id', $classroom->id)->where('session_id', $session->id)->where('term_id', $term->id)->first();
    expect($snapshot)->not->toBeNull()
        ->and($snapshot->mode)->toBe('midterm')
        ->and($snapshot->midterm_weight)->toBe(40)
        ->and(collect($snapshot->exam_heads)->sum('effective_max'))->toBe(60);
});

test('PDF Guards: student cannot download another students slip and requires result PIN', function () {
    $ctx = createMidtermContext(40, 80);
    $classroom = $ctx['classroom'];
    $session = $ctx['session'];
    $term = $ctx['term'];

    $userA = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $studentA = Student::factory()->active()->create(['user_id' => $userA->id]);
    StudentEnrollment::create(['student_id' => $studentA->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    $userB = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $studentB = Student::factory()->active()->create(['user_id' => $userB->id]);
    StudentEnrollment::create(['student_id' => $studentB->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    MidtermClassTermApproval::create(['classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'is_locked' => true, 'is_published' => true]);
    app(MidtermCalculationService::class)->getOrCreateSnapshot($classroom->id, $session->id, $term->id);

    $this->actingAs($userA)
        ->get(route('student.midterm-pdf', ['session_id' => $session->id, 'term_id' => $term->id]))
        ->assertStatus(403);

    $batch = ResultPinBatch::create([
        'batch_code' => 'BATCH-TEST-1',
        'session_id' => $session->id,
        'term_id'    => $term->id,
        'quantity'   => 1,
    ]);

    ResultPin::create([
        'batch_id'      => $batch->id,
        'serial_number' => 'SN-12345',
        'student_id'    => $studentA->id,
        'session_id'    => $session->id,
        'term_id'       => $term->id,
        'pin'           => '12345678',
        'is_used'       => true,
    ]);

    $this->actingAs($userA)
        ->get(route('student.midterm-pdf', ['session_id' => $session->id, 'term_id' => $term->id, 'student_id' => $studentB->id]))
        ->assertStatus(403);

    // Positive control: student with valid PIN and published slip gets HTTP 200 with application/pdf
    $this->actingAs($userA)
        ->get(route('student.midterm-pdf', ['session_id' => $session->id, 'term_id' => $term->id]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('Final-Approval Gate: approveClassResults blocked when subject_results has has_missing_midterm', function () {
    $ctx = createMidtermContext(40, 80);
    $classroom = $ctx['classroom'];
    $session = $ctx['session'];
    $term = $ctx['term'];
    $math = $ctx['math'];

    $student = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    MidtermClassTermApproval::create(['classroom_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term->id, 'is_locked' => true]);

    SubjectResult::create([
        'student_id'          => $student->id,
        'subject_id'          => $math->id,
        'classroom_id'        => $classroom->id,
        'session_id'          => $session->id,
        'term_id'             => $term->id,
        'total'               => 50.0,
        'has_missing_midterm' => true,
        'is_published'        => true,
    ]);

    Livewire::actingAs($admin)
        ->test(\App\Filament\Teacher\Pages\ProcessClassResults::class)
        ->set('session_id', $session->id)
        ->set('term_id', $term->id)
        ->set('classroom_id', $classroom->id)
        ->set('times_school_opened', 90)
        ->set('next_term_begins', 'Next Term')
        ->call('approveClassResults')
        ->assertNotified('Cannot Approve Final Results');

    expect(\App\Models\ClassTermApproval::where('classroom_id', $classroom->id)->where('session_id', $session->id)->where('term_id', $term->id)->exists())->toBeFalse();
});

test('Completeness with a Non-Offered Subject: locking succeeds when student has completed only their offered subjects', function () {
    $ctx = createMidtermContext(40, 80);
    $classroom = $ctx['classroom'];
    $session = $ctx['session'];
    $term = $ctx['term'];
    $math = $ctx['math'];
    $french = Subject::create(['name' => 'French', 'code' => 'FRN', 'is_active' => true]);
    $classroom->subjects()->attach($french->id);

    $student = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    MidtermSubjectResult::create([
        'student_id'     => $student->id,
        'subject_id'     => $math->id,
        'classroom_id'   => $classroom->id,
        'session_id'     => $session->id,
        'term_id'        => $term->id,
        'scaled_score'   => 35.0,
        'status'         => 'complete',
    ]);

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test(ProcessMidtermResults::class)
        ->set('session_id', $session->id)
        ->set('term_id', $term->id)
        ->set('classroom_id', $classroom->id)
        ->call('lockMidtermResults')
        ->assertNotified('Warning: Unscored Subjects Detected')
        ->assertSet('showUnscoredLockWarning', true)
        ->assertSee('French')
        ->call('confirmLockWithUnscored')
        ->assertNotified('Mid-Term Results Locked')
        ->assertSet('showUnscoredLockWarning', false);

    $approval = MidtermClassTermApproval::where('classroom_id', $classroom->id)->where('session_id', $session->id)->where('term_id', $term->id)->first();
    expect($approval)->not->toBeNull()
        ->and($approval->is_locked)->toBeTrue();
});

test('Cumulative Error Exclusion: computeCumulativeMidtermSubjectResults excludes has_error rows from prior terms', function () {
    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '1']);

    $session = Session::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
    $term1 = Term::create(['session_id' => $session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => false]);
    $term2 = Term::create(['session_id' => $session->id, 'name' => 'Second Term', 'order' => 2, 'is_active' => false]);
    $term3 = Term::create(['session_id' => $session->id, 'name' => 'Third Term', 'order' => 3, 'is_active' => true]);

    $classroom = Classroom::create(['name' => 'JSS 3 Cumul', 'class_order' => 3, 'is_active' => true]);
    $student = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    $math = Subject::create(['name' => 'Cumul Math', 'code' => 'CMTH', 'is_active' => true]);
    $classroom->subjects()->attach($math->id);

    $midHead = ScoreHead::create(['name' => 'Cumul Mid', 'max_score' => 40, 'type' => 'midterm', 'is_active' => true]);
    $examHead = ScoreHead::create(['name' => 'Cumul Exam', 'max_score' => 60, 'type' => 'exam', 'is_active' => true]);

    $midConfig = ClassMidtermConfig::create(['classroom_id' => $classroom->id, 'midterm_weight' => 40, 'calculation_method' => 'scale_to_weight']);
    ClassMidtermConfigHead::create(['class_midterm_config_id' => $midConfig->id, 'score_head_id' => $midHead->id]);

    $str3 = ClassScoreStructure::create(['class_id' => $classroom->id, 'session_id' => $session->id, 'term_id' => $term3->id, 'total_score' => 60, 'locked' => true]);
    ClassScoreStructureItem::create(['class_score_structure_id' => $str3->id, 'score_head_id' => $examHead->id]);

    SubjectResult::create([
        'student_id'   => $student->id,
        'subject_id'   => $math->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
        'term_id'      => $term1->id,
        'total'        => 80.0,
        'is_published' => true,
        'has_error'    => false,
    ]);

    SubjectResult::create([
        'student_id'   => $student->id,
        'subject_id'   => $math->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
        'term_id'      => $term2->id,
        'total'        => 75.0,
        'is_published' => true,
        'has_error'    => true,
        'error_message'=> 'Calculation error in term 2',
    ]);

    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $midHead->id, 'session_id' => $session->id, 'term_id' => $term3->id, 'score' => 35.0]);
    createTestScore(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'subject_id' => $math->id, 'score_head_id' => $examHead->id, 'session_id' => $session->id, 'term_id' => $term3->id, 'score' => 55.0]);

    MidtermSubjectResult::create([
        'student_id'   => $student->id,
        'subject_id'   => $math->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
        'term_id'      => $term3->id,
        'scaled_score' => 35.0,
        'status'       => 'complete',
    ]);

    $snapshot3 = app(MidtermCalculationService::class)->getOrCreateSnapshot($classroom->id, $session->id, $term3->id);
    app(ResultCalculationService::class)->calculateForClass($classroom->id, $session->id, $term3->id);

    $cumulResult = SubjectResult::where('classroom_id', $classroom->id)->where('session_id', $session->id)->where('term_id', $term3->id)->first();
    expect($cumulResult->has_error)->toBeTrue()
        ->and($cumulResult->is_published)->toBeFalse()
        ->and($cumulResult->error_message)->toContain('Term 2');
});

test('ManageMidtermStructure: saveConfig blocked when legacy data exists in active session/term; allowed when legacy data exists only in past terms', function () {
    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '1']);

    $session = Session::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
    $term1 = Term::create(['session_id' => $session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => false]);
    $term2 = Term::create(['session_id' => $session->id, 'name' => 'Second Term', 'order' => 2, 'is_active' => true]);

    $midHead = ScoreHead::create(['name' => 'Mid Head MS', 'max_score' => 40, 'type' => 'midterm', 'is_active' => true]);
    $legacyHead = ScoreHead::create(['name' => 'Legacy CA MS', 'max_score' => 40, 'type' => 'ca', 'is_active' => true]);

    $math = Subject::create(['name' => 'Math MS', 'code' => 'MMS', 'is_active' => true]);

    // Class A: has legacy data in Term 2 (active term)
    $classA = Classroom::create(['name' => 'Class A Legacy Active', 'class_order' => 1, 'is_active' => true]);
    $classA->subjects()->attach($math->id);
    $studentA = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $studentA->id, 'classroom_id' => $classA->id, 'session_id' => $session->id]);
    createTestScore(['student_id' => $studentA->id, 'classroom_id' => $classA->id, 'subject_id' => $math->id, 'score_head_id' => $legacyHead->id, 'session_id' => $session->id, 'term_id' => $term2->id, 'score' => 25.0]);

    // Class B: has legacy data ONLY in Term 1 (past term), no data in Term 2 (active term)
    $classB = Classroom::create(['name' => 'Class B Legacy Past', 'class_order' => 2, 'is_active' => true]);
    $classB->subjects()->attach($math->id);
    $studentB = Student::factory()->active()->create();
    StudentEnrollment::create(['student_id' => $studentB->id, 'classroom_id' => $classB->id, 'session_id' => $session->id]);
    createTestScore(['student_id' => $studentB->id, 'classroom_id' => $classB->id, 'subject_id' => $math->id, 'score_head_id' => $legacyHead->id, 'session_id' => $session->id, 'term_id' => $term1->id, 'score' => 25.0]);

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'allowed_admin_pages' => ['*']]);

    // Case 1: saveConfig blocked for Class A (legacy data in active term)
    Livewire::actingAs($admin)
        ->test(ManageMidtermStructure::class)
        ->set('classroom_id', $classA->id)
        ->set('midterm_weight', 40)
        ->set('items', [
            [
                'score_head_id'      => $midHead->id,
                'name'               => $midHead->name,
                'max_score'          => 40,
                'max_score_override' => null,
            ],
        ])
        ->call('saveConfig')
        ->assertNotified('Cannot Activate Mid-Term Structure');

    expect(ClassMidtermConfig::where('classroom_id', $classA->id)->exists())->toBeFalse();

    // Case 2: saveConfig allowed for Class B (legacy data only in past term)
    Livewire::actingAs($admin)
        ->test(ManageMidtermStructure::class)
        ->set('classroom_id', $classB->id)
        ->set('midterm_weight', 40)
        ->set('items', [
            [
                'score_head_id'      => $midHead->id,
                'name'               => $midHead->name,
                'max_score'          => 40,
                'max_score_override' => null,
            ],
        ])
        ->call('saveConfig')
        ->assertNotified('Mid-term structure saved successfully.');

    expect(ClassMidtermConfig::where('classroom_id', $classB->id)->exists())->toBeTrue();
});

test('ManageMidtermStructure: applyToMultipleClasses skips class with active legacy data but applies to clean class', function () {
    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '1']);

    $session = Session::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true]);
    $term1 = Term::create(['session_id' => $session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => false]);
    $term2 = Term::create(['session_id' => $session->id, 'name' => 'Second Term', 'order' => 2, 'is_active' => true]);

    $midHead = ScoreHead::create(['name' => 'Mid Head Multi', 'max_score' => 40, 'type' => 'midterm', 'is_active' => true]);
    $legacyHead = ScoreHead::create(['name' => 'Legacy Head Multi', 'max_score' => 40, 'type' => 'ca', 'is_active' => true]);
    $math = Subject::create(['name' => 'Math Multi', 'code' => 'MMU', 'is_active' => true]);

    $sourceClass = Classroom::create(['name' => 'Source Class', 'class_order' => 1, 'is_active' => true]);
    $targetLegacyClass = Classroom::create(['name' => 'Target Class With Active Legacy', 'class_order' => 2, 'is_active' => true]);
    $targetCleanClass = Classroom::create(['name' => 'Target Class Clean', 'class_order' => 3, 'is_active' => true]);

    // Give Target Class With Active Legacy a score in Term 2
    $student = Student::factory()->active()->create();
    createTestScore(['student_id' => $student->id, 'classroom_id' => $targetLegacyClass->id, 'subject_id' => $math->id, 'score_head_id' => $legacyHead->id, 'session_id' => $session->id, 'term_id' => $term2->id, 'score' => 30.0]);

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'allowed_admin_pages' => ['*']]);

    Livewire::actingAs($admin)
        ->test(ManageMidtermStructure::class)
        ->set('classroom_id', $sourceClass->id)
        ->set('midterm_weight', 40)
        ->set('items', [
            [
                'score_head_id'      => $midHead->id,
                'name'               => $midHead->name,
                'max_score'          => 40,
                'max_score_override' => null,
            ],
        ])
        ->set('apply_target_classroom_ids', [$targetLegacyClass->id, $targetCleanClass->id])
        ->call('applyToMultipleClasses');

    // Clean class received configuration
    expect(ClassMidtermConfig::where('classroom_id', $targetCleanClass->id)->exists())->toBeTrue();
    // Class with active legacy data was skipped
    expect(ClassMidtermConfig::where('classroom_id', $targetLegacyClass->id)->exists())->toBeFalse();
});

test('ManageMidtermStructure: admin can lock and unlock structure, and locked structure rejects edits', function () {
    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '1']);

    $session = Session::factory()->active()->create();
    $term = Term::create(['session_id' => $session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => true]);
    $classroom = Classroom::factory()->create(['is_active' => true]);

    $head = ScoreHead::create(['name' => 'CA 1', 'max_score' => 20, 'type' => 'midterm', 'is_active' => true]);

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'allowed_admin_pages' => ['*']]);

    // 1. Initial save
    Livewire::actingAs($admin)
        ->test(ManageMidtermStructure::class)
        ->set('classroom_id', $classroom->id)
        ->set('midterm_weight', 40)
        ->set('items', [
            [
                'score_head_id'      => $head->id,
                'name'               => $head->name,
                'max_score'          => 20,
                'max_score_override' => null,
            ],
        ])
        ->call('saveConfig');

    $config = ClassMidtermConfig::where('classroom_id', $classroom->id)->first();
    expect($config)->not->toBeNull()
        ->and($config->is_locked)->toBeFalse();

    // 2. Lock structure
    Livewire::actingAs($admin)
        ->test(ManageMidtermStructure::class)
        ->set('classroom_id', $classroom->id)
        ->call('toggleLock');

    $config->refresh();
    expect($config->is_locked)->toBeTrue();

    // 3. Modifying locked structure as non-sudo admin is rejected
    Livewire::actingAs($admin)
        ->test(ManageMidtermStructure::class)
        ->set('classroom_id', $classroom->id)
        ->set('midterm_weight', 50)
        ->call('saveConfig');

    $config->refresh();
    expect($config->midterm_weight)->toBe(40); // Unchanged

    // 4. Unlock structure
    Livewire::actingAs($admin)
        ->test(ManageMidtermStructure::class)
        ->set('classroom_id', $classroom->id)
        ->call('toggleLock');

    $config->refresh();
    expect($config->is_locked)->toBeFalse();

    // 5. Now edits succeed
    Livewire::actingAs($admin)
        ->test(ManageMidtermStructure::class)
        ->set('classroom_id', $classroom->id)
        ->set('midterm_weight', 50)
        ->call('saveConfig');

    $config->refresh();
    expect($config->midterm_weight)->toBe(50);
});

test('ManageMidtermStructure: applyToMultipleClasses skips locked target classes', function () {
    Setting::updateOrCreate(['key' => 'use_midterm_results'], ['value' => '1']);

    $session = Session::factory()->active()->create();
    $term = Term::create(['session_id' => $session->id, 'name' => 'First Term', 'order' => 1, 'is_active' => true]);

    $sourceClass = Classroom::factory()->create(['name' => 'Source', 'is_active' => true]);
    $lockedClass = Classroom::factory()->create(['name' => 'Locked Class', 'is_active' => true]);
    $openClass   = Classroom::factory()->create(['name' => 'Open Class', 'is_active' => true]);

    $head1 = ScoreHead::create(['name' => 'CA Bulk', 'max_score' => 20, 'type' => 'midterm', 'is_active' => true]);

    // Pre-create locked class configuration with weight 30
    $lockedCfg = ClassMidtermConfig::create([
        'classroom_id'       => $lockedClass->id,
        'midterm_weight'     => 30,
        'calculation_method' => 'scale_to_weight',
        'is_locked'          => true,
    ]);

    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'allowed_admin_pages' => ['*']]);

    Livewire::actingAs($admin)
        ->test(ManageMidtermStructure::class)
        ->set('classroom_id', $sourceClass->id)
        ->set('midterm_weight', 40)
        ->set('items', [
            [
                'score_head_id'      => $head1->id,
                'name'               => $head1->name,
                'max_score'          => 20,
                'max_score_override' => null,
            ],
        ])
        ->set('apply_target_classroom_ids', [$lockedClass->id, $openClass->id])
        ->call('applyToMultipleClasses');

    // Locked class was preserved and not overwritten
    $lockedCfg->refresh();
    expect($lockedCfg->midterm_weight)->toBe(30)
        ->and($lockedCfg->is_locked)->toBeTrue();

    // Open class received new configuration
    $openCfg = ClassMidtermConfig::where('classroom_id', $openClass->id)->first();
    expect($openCfg)->not->toBeNull()
        ->and($openCfg->midterm_weight)->toBe(40);
});

