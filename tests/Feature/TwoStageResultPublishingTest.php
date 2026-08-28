<?php

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
use App\Services\ResultCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ── Shared Setup Helper ──────────────────────────────────────────────────────

function setupPublishingContext(): array
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

    $classroom = Classroom::create(['name' => 'JSS 1 Gold', 'class_order' => 1, 'is_active' => true]);

    $math    = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
    $english = Subject::create(['name' => 'English Language', 'code' => 'ENG', 'is_active' => true]);

    $classroom->subjects()->attach([$math->id, $english->id]);

    // Create Score Heads (Classwork: 20, Exam: 80)
    $ca   = ScoreHead::create(['name' => 'Continuous Assessment', 'max_score' => 20, 'is_active' => true]);
    $exam = ScoreHead::create(['name' => 'Examination', 'max_score' => 80, 'is_active' => true]);

    // Structure totaling 100
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

    // Users
    $admin          = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $subjectTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
    $classTeacher   = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

    // Subject Teacher assignment (Math)
    TeacherSubjectAssignment::create([
        'teacher_id'   => $subjectTeacher->id,
        'subject_id'   => $math->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
        'term_id'      => $term->id,
    ]);

    // Class Teacher assignment
    ClassTeacherAssignment::create([
        'teacher_id' => $classTeacher->id,
        'class_id'   => $classroom->id,
        'session_id' => $session->id,
    ]);

    // Enrolled Students
    $studentUser1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $student1 = Student::create(['user_id' => $studentUser1->id, 'full_name' => 'Alice Smith', 'admission_number' => 'ADM001', 'status' => 'active']);
    StudentEnrollment::create(['student_id' => $student1->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    $studentUser2 = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $student2 = Student::create(['user_id' => $studentUser2->id, 'full_name' => 'Bob Jones', 'admission_number' => 'ADM002', 'status' => 'active']);
    StudentEnrollment::create(['student_id' => $student2->id, 'classroom_id' => $classroom->id, 'session_id' => $session->id]);

    return compact(
        'session', 'term', 'classroom', 'math', 'english',
        'ca', 'exam', 'admin', 'subjectTeacher', 'classTeacher',
        'student1', 'studentUser1', 'student2', 'studentUser2'
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Raw Score Entry & Draft Persistence
// ─────────────────────────────────────────────────────────────────────────────

describe('Stage 1: Raw Score Draft Persistence', function () {

    it('saves raw scores without triggering auto-calculation or subject publication', function () {
        $ctx = setupPublishingContext();

        $this->actingAs($ctx['subjectTeacher']);

        // Livewire save score
        Livewire::test(EnterScores::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set('subject_id', $ctx['math']->id)
            ->call('saveScore', $ctx['student1']->id, $ctx['ca']->id, 18)
            ->call('saveScore', $ctx['student1']->id, $ctx['exam']->id, 72);

        // Verify score saved in database
        $this->assertDatabaseHas('scores', [
            'student_id'    => $ctx['student1']->id,
            'subject_id'    => $ctx['math']->id,
            'score_head_id' => $ctx['ca']->id,
            'score'         => 18,
        ]);

        // Verify SubjectResult is NOT published yet
        $subjectResult = SubjectResult::where('student_id', $ctx['student1']->id)
            ->where('subject_id', $ctx['math']->id)
            ->first();

        expect($subjectResult?->is_published)->toBeFalsy();

        // Verify TermResult is NOT finalized
        $termResult = TermResult::where('student_id', $ctx['student1']->id)
            ->where('session_id', $ctx['session']->id)
            ->where('term_id', $ctx['term']->id)
            ->first();

        expect($termResult?->is_finalized)->toBeFalsy();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Stage 1: Subject Teacher Explicit Publishing
// ─────────────────────────────────────────────────────────────────────────────

describe('Stage 1: Subject Teacher Explicit Publishing', function () {

    it('calculates subject results and marks them as published upon subject publish action', function () {
        $ctx = setupPublishingContext();

        // Insert raw scores
        Score::create([
            'student_id'    => $ctx['student1']->id,
            'classroom_id'  => $ctx['classroom']->id,
            'subject_id'    => $ctx['math']->id,
            'score_head_id' => $ctx['ca']->id,
            'session_id'    => $ctx['session']->id,
            'term_id'       => $ctx['term']->id,
            'teacher_id'    => $ctx['subjectTeacher']->id,
            'score'         => 15,
        ]);
        Score::create([
            'student_id'    => $ctx['student1']->id,
            'classroom_id'  => $ctx['classroom']->id,
            'subject_id'    => $ctx['math']->id,
            'score_head_id' => $ctx['exam']->id,
            'session_id'    => $ctx['session']->id,
            'term_id'       => $ctx['term']->id,
            'teacher_id'    => $ctx['subjectTeacher']->id,
            'score'         => 65,
        ]);

        $this->actingAs($ctx['subjectTeacher']);

        Livewire::test(EnterScores::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->set('subject_id', $ctx['math']->id)
            ->call('publishSubject')
            ->assertNotified('Subject scores published successfully!');

        // Check SubjectResult is created and is_published = true
        $res = SubjectResult::where('student_id', $ctx['student1']->id)
            ->where('subject_id', $ctx['math']->id)
            ->first();

        expect($res)->not->toBeNull()
            ->and((float) $res->total)->toBe(80.0)
            ->and($res->is_published)->toBeTrue();

        // TermResult must still NOT be finalized
        $termRes = TermResult::where('student_id', $ctx['student1']->id)->first();
        expect($termRes?->is_finalized)->toBeFalsy();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Stage 2: Class Teacher Widget & Finalization Page
// ─────────────────────────────────────────────────────────────────────────────

describe('Stage 2: Class Teacher Workflow & Progress Widget', function () {

    it('tracks subject publishing progress in ClassResultPublishingWidget', function () {
        $ctx = setupPublishingContext();

        // Insert raw score for Math
        Score::create([
            'student_id'    => $ctx['student1']->id,
            'classroom_id'  => $ctx['classroom']->id,
            'subject_id'    => $ctx['math']->id,
            'score_head_id' => $ctx['exam']->id,
            'session_id'    => $ctx['session']->id,
            'term_id'       => $ctx['term']->id,
            'teacher_id'    => $ctx['subjectTeacher']->id,
            'score'         => 75,
        ]);

        // Publish Math subject results
        app(ResultCalculationService::class)->calculateForSubject(
            $ctx['classroom']->id,
            $ctx['math']->id,
            $ctx['session']->id,
            $ctx['term']->id
        );

        $this->actingAs($ctx['classTeacher']);

        // Widget component test
        Livewire::test(ClassResultPublishingWidget::class)
            ->assertSee('1 / 2') // 1 published out of 2 assigned subjects
            ->assertSee('Pending Finalization');
    });

    it('finalizes class term results when Class Teacher processes the class', function () {
        $ctx = setupPublishingContext();

        // Populate and publish both Math and English subjects
        foreach ([$ctx['math']->id, $ctx['english']->id] as $subjId) {
            Score::create([
                'student_id'    => $ctx['student1']->id,
                'classroom_id'  => $ctx['classroom']->id,
                'subject_id'    => $subjId,
                'score_head_id' => $ctx['exam']->id,
                'session_id'    => $ctx['session']->id,
                'term_id'       => $ctx['term']->id,
                'teacher_id'    => $ctx['subjectTeacher']->id,
                'score'         => 70,
            ]);

            app(ResultCalculationService::class)->calculateForSubject(
                $ctx['classroom']->id,
                $subjId,
                $ctx['session']->id,
                $ctx['term']->id
            );
        }

        $this->actingAs($ctx['classTeacher']);

        Livewire::test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->call('processClassResults')
            ->assertNotified('Class results processed and published successfully!');

        // Verify TermResult is finalized
        $termResult = TermResult::where('student_id', $ctx['student1']->id)
            ->where('session_id', $ctx['session']->id)
            ->where('term_id', $ctx['term']->id)
            ->first();

        expect($termResult)->not->toBeNull()
            ->and($termResult->is_finalized)->toBeTrue()
            ->and((float) $termResult->grand_total)->toBe(140.0);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Student Portal Result Visibility Rules
// ─────────────────────────────────────────────────────────────────────────────

describe('Student Portal Result Visibility', function () {

    it('hides result details and displays processing status for un-finalized terms', function () {
        $ctx = setupPublishingContext();

        $this->actingAs($ctx['studentUser1']);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->assertSet('loaded', false)
            ->assertSee('Results Processing in Progress');
    });

    it('renders complete result scorecard once class results are finalized', function () {
        $ctx = setupPublishingContext();

        // Populate scores
        Score::create([
            'student_id'    => $ctx['student1']->id,
            'classroom_id'  => $ctx['classroom']->id,
            'subject_id'    => $ctx['math']->id,
            'score_head_id' => $ctx['exam']->id,
            'session_id'    => $ctx['session']->id,
            'term_id'       => $ctx['term']->id,
            'teacher_id'    => $ctx['subjectTeacher']->id,
            'score'         => 80,
        ]);

        // Publish Math and Calculate Class
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

        $this->actingAs($ctx['studentUser1']);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->assertSet('loaded', true)
            ->assertDontSee('Results Processing in Progress');
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Authorization & Role Checks
// ─────────────────────────────────────────────────────────────────────────────

describe('Authorization & Role Access Rules', function () {

    it('denies ProcessClassResults access to non-class subject teachers', function () {
        $ctx = setupPublishingContext();

        $this->actingAs($ctx['subjectTeacher']);

        expect(ProcessClassResults::canAccess())->toBeFalse()
            ->and(ClassResultPublishingWidget::canView())->toBeFalse();
    });

    it('grants ProcessClassResults access to Class Teachers and Admins', function () {
        $ctx = setupPublishingContext();

        // Class Teacher
        $this->actingAs($ctx['classTeacher']);
        expect(ProcessClassResults::canAccess())->toBeTrue()
            ->and(ClassResultPublishingWidget::canView())->toBeTrue();

        // Admin
        $this->actingAs($ctx['admin']);
        expect(ProcessClassResults::canAccess())->toBeTrue()
            ->and(ClassResultPublishingWidget::canView())->toBeTrue();
    });

});
