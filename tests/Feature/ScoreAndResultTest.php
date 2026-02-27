<?php

use App\Filament\Pages\ManageClassScoreStructure;
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
use App\Models\User;
use App\Policies\ClassScoreStructurePolicy;
use App\Policies\ScorePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Shared setup helpers ─────────────────────────────────────────────────────

function makeAcademicContext(): array
{
    $session = Session::create([
        'name'       => '2024-2025',
        'start_year' => 2024,
        'end_year'   => 2025,
        'is_active'  => true,
    ]);

    $term = Term::create([
        'session_id' => $session->id,
        'name'       => 'First Term',
        'order'      => 1,
        'is_active'  => true,
    ]);

    $classroom = Classroom::create(['name' => 'JSS1', 'class_order' => 1, 'is_active' => true]);

    $math    = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
    $english = Subject::create(['name' => 'English',     'code' => 'ENG',  'is_active' => true]);

    return compact('session', 'term', 'classroom', 'math', 'english');
}

function makeUsers(): array
{
    $sudo    = User::factory()->create(['role' => 'sudo',    'is_active' => true]);
    $admin   = User::factory()->create(['role' => 'admin',   'is_active' => true]);
    $teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
    $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

    return compact('sudo', 'admin', 'teacher', 'student');
}

function makeScoreHeads(): array
{
    $classwork = ScoreHead::create(['name' => 'Classwork', 'max_score' => 10, 'is_active' => true]);
    $exam      = ScoreHead::create(['name' => 'Exam',      'max_score' => 80, 'is_active' => true]);

    return compact('classwork', 'exam');
}

function makeStructure(Classroom $classroom, Session $session, Term $term, array $scoreHeads): ClassScoreStructure
{
    $structure = ClassScoreStructure::create([
        'class_id'    => $classroom->id,
        'session_id'  => $session->id,
        'term_id'     => $term->id,
        'total_score' => 90,
        'locked'      => false,
    ]);

    foreach ($scoreHeads as $sh) {
        ClassScoreStructureItem::create([
            'class_score_structure_id' => $structure->id,
            'score_head_id'            => $sh->id,
            'max_score_override'       => null,
        ]);
    }

    return $structure;
}

function makeEnrolledStudent(Classroom $classroom, Session $session): Student
{
    $student = Student::create(['full_name' => 'Alice Johnson', 'status' => 'active']);
    StudentEnrollment::create([
        'student_id'   => $student->id,
        'classroom_id' => $classroom->id,
        'session_id'   => $session->id,
    ]);
    return $student;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. ScoreHead Model
// ─────────────────────────────────────────────────────────────────────────────

describe('ScoreHead model', function () {

    it('can be created with required fields', function () {
        $sh = ScoreHead::create(['name' => 'Quiz', 'max_score' => 20, 'is_active' => true]);

        expect($sh->name)->toBe('Quiz')
            ->and($sh->max_score)->toBe(20)
            ->and($sh->is_active)->toBeTrue();

        $this->assertDatabaseHas('score_heads', ['name' => 'Quiz', 'max_score' => 20]);
    });

    it('enforces unique name constraint', function () {
        ScoreHead::create(['name' => 'Classwork', 'max_score' => 10, 'is_active' => true]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        ScoreHead::create(['name' => 'Classwork', 'max_score' => 15, 'is_active' => true]);
    });

    it('active scope filters inactive heads', function () {
        ScoreHead::create(['name' => 'Active Head', 'max_score' => 10, 'is_active' => true]);
        ScoreHead::create(['name' => 'Inactive Head', 'max_score' => 10, 'is_active' => false]);

        $active = ScoreHead::active()->get();

        expect($active)->toHaveCount(1)
            ->and($active->first()->name)->toBe('Active Head');
    });

    it('stores created_by for auditing', function () {
        ['admin' => $admin] = makeUsers();

        $sh = ScoreHead::create([
            'name'       => 'Test Head',
            'max_score'  => 25,
            'is_active'  => true,
            'created_by' => $admin->id,
        ]);

        expect($sh->creator->id)->toBe($admin->id);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 2. ClassScoreStructure Model
// ─────────────────────────────────────────────────────────────────────────────

describe('ClassScoreStructure model', function () {

    it('can be created for a class/session/term', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom] = makeAcademicContext();

        $structure = ClassScoreStructure::create([
            'class_id'    => $classroom->id,
            'session_id'  => $session->id,
            'term_id'     => $term->id,
            'total_score' => 0,
            'locked'      => false,
        ]);

        expect($structure->locked)->toBeFalse()
            ->and($structure->total_score)->toBe(0);

        $this->assertDatabaseHas('class_score_structures', [
            'class_id'   => $classroom->id,
            'session_id' => $session->id,
            'term_id'    => $term->id,
        ]);
    });

    it('enforces unique class/session/term constraint', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom] = makeAcademicContext();

        ClassScoreStructure::create([
            'class_id'   => $classroom->id,
            'session_id' => $session->id,
            'term_id'    => $term->id,
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        ClassScoreStructure::create([
            'class_id'   => $classroom->id,
            'session_id' => $session->id,
            'term_id'    => $term->id,
        ]);
    });

    it('recalculates total using overrides where present', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom] = makeAcademicContext();
        ['classwork' => $classwork, 'exam' => $exam] = makeScoreHeads();

        $structure = ClassScoreStructure::create([
            'class_id'   => $classroom->id,
            'session_id' => $session->id,
            'term_id'    => $term->id,
        ]);

        // classwork default 10, exam default 80 → total should be 90
        ClassScoreStructureItem::create([
            'class_score_structure_id' => $structure->id,
            'score_head_id'            => $classwork->id,
        ]);
        ClassScoreStructureItem::create([
            'class_score_structure_id' => $structure->id,
            'score_head_id'            => $exam->id,
        ]);

        $structure->recalculateTotal();

        expect($structure->fresh()->total_score)->toBe(90);
    });

    it('uses override when calculating total', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom] = makeAcademicContext();
        ['classwork' => $classwork] = makeScoreHeads();

        $structure = ClassScoreStructure::create([
            'class_id'   => $classroom->id,
            'session_id' => $session->id,
            'term_id'    => $term->id,
        ]);

        // Override classwork from 10 to 20
        ClassScoreStructureItem::create([
            'class_score_structure_id' => $structure->id,
            'score_head_id'            => $classwork->id,
            'max_score_override'       => 20,
        ]);

        $structure->recalculateTotal();

        expect($structure->fresh()->total_score)->toBe(20);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ClassScoreStructureItem — effective_max
// ─────────────────────────────────────────────────────────────────────────────

describe('ClassScoreStructureItem effective_max', function () {

    it('returns override when set', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom] = makeAcademicContext();
        ['classwork' => $classwork] = makeScoreHeads();

        $structure = ClassScoreStructure::create([
            'class_id'   => $classroom->id,
            'session_id' => $session->id,
            'term_id'    => $term->id,
        ]);

        $item = ClassScoreStructureItem::create([
            'class_score_structure_id' => $structure->id,
            'score_head_id'            => $classwork->id,
            'max_score_override'       => 15,
        ]);

        expect($item->effective_max_score)->toBe(15);
    });

    it('falls back to score head default when no override', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom] = makeAcademicContext();
        ['classwork' => $classwork] = makeScoreHeads();

        $structure = ClassScoreStructure::create([
            'class_id'   => $classroom->id,
            'session_id' => $session->id,
            'term_id'    => $term->id,
        ]);

        $item = ClassScoreStructureItem::create([
            'class_score_structure_id' => $structure->id,
            'score_head_id'            => $classwork->id,
        ]);

        // classwork max_score is 10
        expect($item->effective_max_score)->toBe(10);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Score Model
// ─────────────────────────────────────────────────────────────────────────────

describe('Score model', function () {

    it('can be created and stored', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom, 'math' => $math] = makeAcademicContext();
        ['teacher' => $teacher] = makeUsers();
        ['classwork' => $classwork] = makeScoreHeads();
        $student = makeEnrolledStudent($classroom, $session);

        $score = Score::create([
            'student_id'    => $student->id,
            'classroom_id'  => $classroom->id,
            'subject_id'    => $math->id,
            'score_head_id' => $classwork->id,
            'session_id'    => $session->id,
            'term_id'       => $term->id,
            'teacher_id'    => $teacher->id,
            'score'         => 8.5,
        ]);

        expect((float) $score->score)->toBe(8.5);
        $this->assertDatabaseHas('scores', ['student_id' => $student->id, 'score' => 8.5]);
    });

    it('enforces unique student/subject/score-head/session/term constraint', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom, 'math' => $math] = makeAcademicContext();
        ['teacher' => $teacher] = makeUsers();
        ['classwork' => $classwork] = makeScoreHeads();
        $student = makeEnrolledStudent($classroom, $session);

        $base = [
            'student_id'    => $student->id,
            'classroom_id'  => $classroom->id,
            'subject_id'    => $math->id,
            'score_head_id' => $classwork->id,
            'session_id'    => $session->id,
            'term_id'       => $term->id,
            'teacher_id'    => $teacher->id,
        ];

        Score::create([...$base, 'score' => 7]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Score::create([...$base, 'score' => 9]);
    });

    it('updateOrCreate updates without throwing on duplicate', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom, 'math' => $math] = makeAcademicContext();
        ['teacher' => $teacher] = makeUsers();
        ['classwork' => $classwork] = makeScoreHeads();
        $student = makeEnrolledStudent($classroom, $session);

        $lookup = [
            'student_id'    => $student->id,
            'subject_id'    => $math->id,
            'score_head_id' => $classwork->id,
            'session_id'    => $session->id,
            'term_id'       => $term->id,
        ];
        $data = ['classroom_id' => $classroom->id, 'teacher_id' => $teacher->id, 'score' => 5];

        Score::updateOrCreate($lookup, $data);
        Score::updateOrCreate($lookup, ['score' => 9] + $data);

        expect(Score::count())->toBe(1)
            ->and((float) Score::first()->score)->toBe(9.0);
    });

    it('score scopes filter correctly', function () {
        ['session' => $session, 'term' => $term, 'classroom' => $classroom, 'math' => $math, 'english' => $english] = makeAcademicContext();
        ['teacher' => $teacher] = makeUsers();
        ['classwork' => $classwork, 'exam' => $exam] = makeScoreHeads();
        $student = makeEnrolledStudent($classroom, $session);

        Score::create([
            'student_id'    => $student->id,
            'classroom_id'  => $classroom->id,
            'subject_id'    => $math->id,
            'score_head_id' => $classwork->id,
            'session_id'    => $session->id,
            'term_id'       => $term->id,
            'teacher_id'    => $teacher->id,
            'score'         => 8,
        ]);
        Score::create([
            'student_id'    => $student->id,
            'classroom_id'  => $classroom->id,
            'subject_id'    => $english->id,
            'score_head_id' => $exam->id,
            'session_id'    => $session->id,
            'term_id'       => $term->id,
            'teacher_id'    => $teacher->id,
            'score'         => 60,
        ]);

        expect(Score::forSubject($math->id)->count())->toBe(1)
            ->and(Score::forSubject($english->id)->count())->toBe(1)
            ->and(Score::forSession($session->id)->count())->toBe(2)
            ->and(Score::forStudent($student->id)->count())->toBe(2);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 5. ScorePolicy
// ─────────────────────────────────────────────────────────────────────────────

describe('ScorePolicy', function () {

    beforeEach(function () {
        $ctx = makeAcademicContext();
        $this->session   = $ctx['session'];
        $this->term      = $ctx['term'];
        $this->classroom = $ctx['classroom'];
        $this->math      = $ctx['math'];
        $this->english   = $ctx['english'];

        $users           = makeUsers();
        $this->sudo      = $users['sudo'];
        $this->admin     = $users['admin'];
        $this->teacher   = $users['teacher'];
        $this->student   = $users['student'];

        // Teacher is a subject teacher for Math only
        TeacherSubjectAssignment::create([
            'teacher_id'   => $this->teacher->id,
            'subject_id'   => $this->math->id,
            'classroom_id' => $this->classroom->id,
            'session_id'   => $this->session->id,
            'term_id'      => $this->term->id,
        ]);

        $this->policy = new ScorePolicy();
    });

    it('allows admin to viewAny scores', function () {
        expect($this->policy->viewAny($this->admin))->toBeTrue();
    });

    it('allows teacher to viewAny scores', function () {
        expect($this->policy->viewAny($this->teacher))->toBeTrue();
    });

    it('blocks student from viewAny scores', function () {
        expect($this->policy->viewAny($this->student))->toBeFalse();
    });

    it('allows admin to enter score for any subject', function () {
        expect($this->policy->enterScore(
            $this->admin,
            $this->math->id, $this->classroom->id, $this->session->id, $this->term->id
        ))->toBeTrue();

        expect($this->policy->enterScore(
            $this->admin,
            $this->english->id, $this->classroom->id, $this->session->id, $this->term->id
        ))->toBeTrue();
    });

    it('allows subject teacher to enter score for their assigned subject', function () {
        expect($this->policy->enterScore(
            $this->teacher,
            $this->math->id, $this->classroom->id, $this->session->id, $this->term->id
        ))->toBeTrue();
    });

    it('blocks subject teacher from entering score for non-assigned subject', function () {
        expect($this->policy->enterScore(
            $this->teacher,
            $this->english->id, $this->classroom->id, $this->session->id, $this->term->id
        ))->toBeFalse();
    });

    it('allows class teacher to enter score for any subject in their class', function () {
        // Elevate teacher to class teacher
        ClassTeacherAssignment::create([
            'teacher_id' => $this->teacher->id,
            'class_id'   => $this->classroom->id,
            'session_id' => $this->session->id,
        ]);

        expect($this->policy->enterScore(
            $this->teacher,
            $this->english->id, $this->classroom->id, $this->session->id, $this->term->id
        ))->toBeTrue();
    });

    it('blocks student from entering any score', function () {
        expect($this->policy->enterScore(
            $this->student,
            $this->math->id, $this->classroom->id, $this->session->id, $this->term->id
        ))->toBeFalse();
    });

    it('allows admin to delete scores', function () {
        $scoreHead = ScoreHead::create(['name' => 'Quiz', 'max_score' => 10, 'is_active' => true]);
        $enrolledStudent = makeEnrolledStudent($this->classroom, $this->session);

        $score = Score::create([
            'student_id'    => $enrolledStudent->id,
            'classroom_id'  => $this->classroom->id,
            'subject_id'    => $this->math->id,
            'score_head_id' => $scoreHead->id,
            'session_id'    => $this->session->id,
            'term_id'       => $this->term->id,
            'teacher_id'    => $this->teacher->id,
            'score'         => 7,
        ]);

        expect($this->policy->delete($this->admin, $score))->toBeTrue();
    });

    it('blocks teacher from deleting scores', function () {
        $scoreHead = ScoreHead::create(['name' => 'Quiz', 'max_score' => 10, 'is_active' => true]);
        $enrolledStudent = makeEnrolledStudent($this->classroom, $this->session);

        $score = Score::create([
            'student_id'    => $enrolledStudent->id,
            'classroom_id'  => $this->classroom->id,
            'subject_id'    => $this->math->id,
            'score_head_id' => $scoreHead->id,
            'session_id'    => $this->session->id,
            'term_id'       => $this->term->id,
            'teacher_id'    => $this->teacher->id,
            'score'         => 7,
        ]);

        expect($this->policy->delete($this->teacher, $score))->toBeFalse();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 6. ClassScoreStructurePolicy
// ─────────────────────────────────────────────────────────────────────────────

describe('ClassScoreStructurePolicy', function () {

    beforeEach(function () {
        $ctx             = makeAcademicContext();
        $this->session   = $ctx['session'];
        $this->term      = $ctx['term'];
        $this->classroom = $ctx['classroom'];

        $users         = makeUsers();
        $this->sudo    = $users['sudo'];
        $this->admin   = $users['admin'];
        $this->teacher = $users['teacher'];

        $this->policy = new ClassScoreStructurePolicy();
    });

    it('allows admin to create and view structures', function () {
        expect($this->policy->create($this->admin))->toBeTrue()
            ->and($this->policy->viewAny($this->admin))->toBeTrue();
    });

    it('blocks teacher from creating structures', function () {
        expect($this->policy->create($this->teacher))->toBeFalse();
    });

    it('allows admin to update an unlocked structure', function () {
        $structure = ClassScoreStructure::create([
            'class_id'   => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'locked'     => false,
        ]);

        expect($this->policy->update($this->admin, $structure))->toBeTrue();
    });

    it('blocks admin from updating a locked structure', function () {
        $structure = ClassScoreStructure::create([
            'class_id'   => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'locked'     => true,
        ]);

        expect($this->policy->update($this->admin, $structure))->toBeFalse();
    });

    it('allows sudo to update a locked structure', function () {
        $structure = ClassScoreStructure::create([
            'class_id'   => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'locked'     => true,
        ]);

        expect($this->policy->update($this->sudo, $structure))->toBeTrue();
    });

    it('blocks everyone from deleting a structure', function () {
        $structure = ClassScoreStructure::create([
            'class_id'   => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
        ]);

        expect($this->policy->delete($this->sudo, $structure))->toBeFalse()
            ->and($this->policy->delete($this->admin, $structure))->toBeFalse()
            ->and($this->policy->delete($this->teacher, $structure))->toBeFalse();
    });

    it('allows admin to lock a structure', function () {
        $structure = ClassScoreStructure::create([
            'class_id'   => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
        ]);

        expect($this->policy->lock($this->admin, $structure))->toBeTrue();
    });

    it('only allows sudo to unlock a structure', function () {
        $structure = ClassScoreStructure::create([
            'class_id'   => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'locked'     => true,
        ]);

        expect($this->policy->unlock($this->admin, $structure))->toBeFalse()
            ->and($this->policy->unlock($this->sudo, $structure))->toBeTrue();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 7. ManageClassScoreStructure — Livewire page
// ─────────────────────────────────────────────────────────────────────────────

describe('ManageClassScoreStructure Livewire page', function () {

    beforeEach(function () {
        $ctx             = makeAcademicContext();
        $this->session   = $ctx['session'];
        $this->term      = $ctx['term'];
        $this->classroom = $ctx['classroom'];
        $this->math      = $ctx['math'];

        $users        = makeUsers();
        $this->sudo   = $users['sudo'];
        $this->admin  = $users['admin'];
        $this->teacher = $users['teacher'];

        ['classwork' => $this->classwork, 'exam' => $this->exam] = makeScoreHeads();
    });

    it('is accessible by admin', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->assertSuccessful();
    });

    it('is accessible by sudo', function () {
        $this->actingAs($this->sudo);

        Livewire::test(ManageClassScoreStructure::class)
            ->assertSuccessful();
    });

    it('pre-populates the active session on mount', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->assertSet('session_id', $this->session->id)
            ->assertSet('term_id', $this->term->id);
    });

    it('resets structure when session changes', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('classroom_id', $this->classroom->id)
            ->set('session_id', 999)  // change session
            ->assertSet('term_id', null)
            ->assertSet('classroom_id', null)
            ->assertSet('items', []);
    });

    it('loads empty structure when none exists for selected class', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->assertSet('structureId', null)
            ->assertSet('items', []);
    });

    it('loads existing structure when one exists', function () {
        makeStructure($this->classroom, $this->session, $this->term, [$this->classwork, $this->exam]);
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->assertSet('locked', false)
            ->assertCount('items', 2);
    });

    it('adds a score head to the structure', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('selectedScoreHeadId', $this->classwork->id)
            ->call('addItem')
            ->assertCount('items', 1)
            ->assertSet('totalScore', 10);
    });

    it('prevents adding the same score head twice', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('selectedScoreHeadId', $this->classwork->id)
            ->call('addItem')
            ->set('selectedScoreHeadId', $this->classwork->id)
            ->call('addItem')
            ->assertCount('items', 1);
    });

    it('rejects adding a score head that would push total above 100', function () {
        $huge = ScoreHead::create(['name' => 'Huge', 'max_score' => 99, 'is_active' => true]);
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('selectedScoreHeadId', $this->exam->id)   // 80
            ->call('addItem')
            ->set('selectedScoreHeadId', $huge->id)         // 80+99 = 179 → rejected
            ->call('addItem')
            ->assertCount('items', 1)
            ->assertSet('totalScore', 80);
    });

    it('removes a score head from the structure', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('selectedScoreHeadId', $this->classwork->id)
            ->call('addItem')
            ->call('removeItem', 0)
            ->assertCount('items', 0)
            ->assertSet('totalScore', 0);
    });

    it('saves structure to the database', function () {
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('selectedScoreHeadId', $this->classwork->id)
            ->call('addItem')
            ->call('saveStructure');

        $this->assertDatabaseHas('class_score_structures', [
            'class_id'   => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
        ]);

        $this->assertDatabaseHas('class_score_structure_items', [
            'score_head_id' => $this->classwork->id,
        ]);
    });

    it('locks a structure', function () {
        $structure = makeStructure($this->classroom, $this->session, $this->term, [$this->classwork]);
        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->call('toggleLock')
            ->assertSet('locked', true);

        $this->assertDatabaseHas('class_score_structures', [
            'id'     => $structure->id,
            'locked' => true,
        ]);
    });

    it('blocks admin from unlocking a locked structure', function () {
        $structure = makeStructure($this->classroom, $this->session, $this->term, [$this->classwork]);
        $structure->update(['locked' => true]);

        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->call('toggleLock')
            ->assertSet('locked', true); // still locked
    });

    it('allows sudo to unlock a locked structure', function () {
        $structure = makeStructure($this->classroom, $this->session, $this->term, [$this->classwork]);
        $structure->update(['locked' => true]);

        $this->actingAs($this->sudo);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->call('toggleLock')
            ->assertSet('locked', false);
    });

    it('blocks removal of a score head when scores already exist', function () {
        $structure = makeStructure($this->classroom, $this->session, $this->term, [$this->classwork]);
        $student   = makeEnrolledStudent($this->classroom, $this->session);

        Score::create([
            'student_id'    => $student->id,
            'classroom_id'  => $this->classroom->id,
            'subject_id'    => $this->math->id,
            'score_head_id' => $this->classwork->id,
            'session_id'    => $this->session->id,
            'term_id'       => $this->term->id,
            'teacher_id'    => $this->admin->id,
            'score'         => 8,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ManageClassScoreStructure::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->call('removeItem', 0)
            ->assertCount('items', 1); // still there
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// 8. EnterScores — Livewire page (teacher panel)
// ─────────────────────────────────────────────────────────────────────────────

describe('EnterScores Livewire page', function () {

    beforeEach(function () {
        $ctx             = makeAcademicContext();
        $this->session   = $ctx['session'];
        $this->term      = $ctx['term'];
        $this->classroom = $ctx['classroom'];
        $this->math      = $ctx['math'];
        $this->english   = $ctx['english'];

        $users          = makeUsers();
        $this->admin    = $users['admin'];
        $this->teacher  = $users['teacher'];
        $this->student  = $users['student'];

        // Subject teacher for Math only
        TeacherSubjectAssignment::create([
            'teacher_id'   => $this->teacher->id,
            'subject_id'   => $this->math->id,
            'classroom_id' => $this->classroom->id,
            'session_id'   => $this->session->id,
            'term_id'      => $this->term->id,
        ]);

        ['classwork' => $this->classwork, 'exam' => $this->exam] = makeScoreHeads();

        $this->structure = makeStructure(
            $this->classroom, $this->session, $this->term,
            [$this->classwork, $this->exam]
        );

        $this->enrolledStudent = makeEnrolledStudent($this->classroom, $this->session);
    });

    it('is accessible by teacher', function () {
        $this->actingAs($this->teacher);

        Livewire::test(EnterScores::class)
            ->assertSuccessful();
    });

    it('is accessible by admin', function () {
        $this->actingAs($this->admin);

        Livewire::test(EnterScores::class)
            ->assertSuccessful();
    });

    it('pre-populates active session on mount', function () {
        $this->actingAs($this->teacher);

        Livewire::test(EnterScores::class)
            ->assertSet('session_id', $this->session->id)
            ->assertSet('term_id', $this->term->id);
    });

    it('only shows classrooms where teacher is assigned', function () {
        $otherClassroom = Classroom::create(['name' => 'JSS2', 'class_order' => 2, 'is_active' => true]);

        $this->actingAs($this->teacher);

        $component = Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id);

        // authorizedClassrooms should only include JSS1 (the assigned class)
        expect($component->get('authorizedClassrooms'))
            ->toHaveCount(1)
            ->and($component->get('authorizedClassrooms')->first()->id)->toBe($this->classroom->id);
    });

    it('only shows subjects assigned to the teacher for the class', function () {
        $this->actingAs($this->teacher);

        $component = Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id);

        // Only Math is assigned — English should not appear
        expect($component->get('authorizedSubjects'))
            ->toHaveCount(1)
            ->and($component->get('authorizedSubjects')->first()->id)->toBe($this->math->id);
    });

    it('shows all subjects for a class teacher', function () {
        // Assign English too (via another teacher to satisfy unique constraint)
        $otherTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        TeacherSubjectAssignment::create([
            'teacher_id'   => $otherTeacher->id,
            'subject_id'   => $this->english->id,
            'classroom_id' => $this->classroom->id,
            'session_id'   => $this->session->id,
            'term_id'      => $this->term->id,
        ]);

        // Elevate our teacher to class teacher
        ClassTeacherAssignment::create([
            'teacher_id' => $this->teacher->id,
            'class_id'   => $this->classroom->id,
            'session_id' => $this->session->id,
        ]);

        $this->actingAs($this->teacher);

        $component = Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id);

        // Class teacher sees both Math and English
        expect($component->get('authorizedSubjects'))->toHaveCount(2);
    });

    it('loads students and score heads when subject is selected', function () {
        $this->actingAs($this->teacher);

        Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('subject_id', $this->math->id)
            ->assertSet('loaded', true)
            ->assertSet('structureExists', true)
            ->assertCount('students', 1)
            ->assertCount('scoreHeads', 2);
    });

    it('saves new scores to the database', function () {
        $this->actingAs($this->teacher);

        $component = Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('subject_id', $this->math->id);

        $studentId   = $this->enrolledStudent->id;
        $classworkId = $this->classwork->id;
        $examId      = $this->exam->id;

        $component
            ->set("scores.{$studentId}.{$classworkId}", '8')
            ->set("scores.{$studentId}.{$examId}", '65')
            ->call('saveScores');

        $this->assertDatabaseHas('scores', [
            'student_id'    => $studentId,
            'subject_id'    => $this->math->id,
            'score_head_id' => $classworkId,
            'score'         => 8,
        ]);
        $this->assertDatabaseHas('scores', [
            'student_id'    => $studentId,
            'subject_id'    => $this->math->id,
            'score_head_id' => $examId,
            'score'         => 65,
        ]);
    });

    it('updates an existing score instead of creating a duplicate', function () {
        $this->actingAs($this->teacher);

        $component = Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('subject_id', $this->math->id);

        $sid = $this->enrolledStudent->id;
        $cid = $this->classwork->id;

        $component->set("scores.{$sid}.{$cid}", '5')->call('saveScores');
        $component->set("scores.{$sid}.{$cid}", '9')->call('saveScores');

        expect(Score::where('student_id', $sid)->where('score_head_id', $cid)->count())->toBe(1)
            ->and((float) Score::where('student_id', $sid)->where('score_head_id', $cid)->value('score'))->toBe(9.0);
    });

    it('deletes score when the cell is cleared', function () {
        $this->actingAs($this->teacher);

        $component = Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('subject_id', $this->math->id);

        $sid = $this->enrolledStudent->id;
        $cid = $this->classwork->id;

        // Save a score first
        $component->set("scores.{$sid}.{$cid}", '7')->call('saveScores');
        expect(Score::where('student_id', $sid)->where('score_head_id', $cid)->exists())->toBeTrue();

        // Clear the score
        $component->set("scores.{$sid}.{$cid}", '')->call('saveScores');
        expect(Score::where('student_id', $sid)->where('score_head_id', $cid)->exists())->toBeFalse();
    });

    it('blocks saving scores above the effective max', function () {
        $this->actingAs($this->teacher);

        $component = Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('subject_id', $this->math->id);

        $sid = $this->enrolledStudent->id;
        $cid = $this->classwork->id; // max 10

        // Try to save 99 for a score head with max 10
        $component->set("scores.{$sid}.{$cid}", '99')->call('saveScores');

        expect(Score::where('student_id', $sid)->where('score_head_id', $cid)->exists())->toBeFalse();
    });

    it('shows no-structure warning when none is configured', function () {
        $classroom2 = Classroom::create(['name' => 'JSS3', 'class_order' => 3, 'is_active' => true]);
        $otherTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

        TeacherSubjectAssignment::create([
            'teacher_id'   => $otherTeacher->id,
            'subject_id'   => $this->math->id,
            'classroom_id' => $classroom2->id,
            'session_id'   => $this->session->id,
            'term_id'      => $this->term->id,
        ]);

        $this->actingAs($otherTeacher);

        Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $classroom2->id)
            ->set('subject_id', $this->math->id)
            ->assertSet('loaded', true)
            ->assertSet('structureExists', false);
    });

    it('shows no-students warning when no one is enrolled', function () {
        $classroom2  = Classroom::create(['name' => 'JSS3', 'class_order' => 3, 'is_active' => true]);
        $otherTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

        TeacherSubjectAssignment::create([
            'teacher_id'   => $otherTeacher->id,
            'subject_id'   => $this->math->id,
            'classroom_id' => $classroom2->id,
            'session_id'   => $this->session->id,
            'term_id'      => $this->term->id,
        ]);
        makeStructure($classroom2, $this->session, $this->term, [$this->classwork]);

        $this->actingAs($otherTeacher);

        Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $classroom2->id)
            ->set('subject_id', $this->math->id)
            ->assertSet('loaded', true)
            ->assertSet('structureExists', true)
            ->assertCount('students', 0);
    });

    it('blocks saving scores for a non-assigned subject', function () {
        $this->actingAs($this->teacher);

        // Force English into state (simulating a tampered request)
        $component = Livewire::test(EnterScores::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->set('classroom_id', $this->classroom->id)
            ->set('subject_id', $this->english->id); // teacher NOT assigned to English

        $sid = $this->enrolledStudent->id;
        $cid = $this->classwork->id;

        $component->set("scores.{$sid}.{$cid}", '5')->call('saveScores');

        // saveScores re-checks authorization and aborts — no score saved
        expect(Score::where('subject_id', $this->english->id)->exists())->toBeFalse();
    });

});
