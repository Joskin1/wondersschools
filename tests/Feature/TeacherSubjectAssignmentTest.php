<?php

use App\Models\Classroom;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create admin user
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    // Create active session and term
    $this->session = Session::factory()->create([
        'is_active' => true,
        'start_year' => 2026,
        'end_year' => 2027,
    ]);

    $this->term = Term::factory()->create([
        'session_id' => $this->session->id,
        'is_active' => true,
        'name' => 'First Term',
        'order' => 1,
    ]);

    // Create teachers
    $this->teacher1 = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
        'name' => 'Teacher One',
    ]);

    $this->teacher2 = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
        'name' => 'Teacher Two',
    ]);

    // Create classrooms
    $this->classroom1 = Classroom::factory()->create(['name' => 'JSS 1A']);
    $this->classroom2 = Classroom::factory()->create(['name' => 'JSS 2B']);

    // Create subjects
    $this->subject1 = Subject::factory()->create(['name' => 'Mathematics']);
    $this->subject2 = Subject::factory()->create(['name' => 'English']);
});

test('can create teacher subject assignment', function () {
    $assignment = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    expect($assignment)->toBeInstanceOf(TeacherSubjectAssignment::class)
        ->and($assignment->teacher_id)->toBe($this->teacher1->id)
        ->and($assignment->subject_id)->toBe($this->subject1->id)
        ->and($assignment->classroom_id)->toBe($this->classroom1->id);
});

test('assignment has correct relationships', function () {
    $assignment = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    expect($assignment->teacher)->toBeInstanceOf(User::class)
        ->and($assignment->teacher->name)->toBe('Teacher One')
        ->and($assignment->subject)->toBeInstanceOf(Subject::class)
        ->and($assignment->subject->name)->toBe('Mathematics')
        ->and($assignment->classroom)->toBeInstanceOf(Classroom::class)
        ->and($assignment->classroom->name)->toBe('JSS 1A')
        ->and($assignment->session)->toBeInstanceOf(Session::class)
        ->and($assignment->term)->toBeInstanceOf(Term::class);
});

test('can check if teacher is assigned to subject', function () {
    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    $isAssigned = TeacherSubjectAssignment::isAssigned(
        $this->teacher1->id,
        $this->subject1->id,
        $this->classroom1->id,
        $this->session->id,
        $this->term->id
    );

    expect($isAssigned)->toBeTrue();

    $notAssigned = TeacherSubjectAssignment::isAssigned(
        $this->teacher2->id,
        $this->subject1->id,
        $this->classroom1->id,
        $this->session->id,
        $this->term->id
    );

    expect($notAssigned)->toBeFalse();
});

test('can scope assignments by teacher', function () {
    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher2->id,
        'subject_id' => $this->subject2->id,
        'classroom_id' => $this->classroom2->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    $teacher1Assignments = TeacherSubjectAssignment::forTeacher($this->teacher1->id)->get();

    expect($teacher1Assignments)->toHaveCount(1)
        ->and($teacher1Assignments->first()->teacher_id)->toBe($this->teacher1->id);
});

test('can scope assignments by classroom', function () {
    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject2->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher2->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom2->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    $classroom1Assignments = TeacherSubjectAssignment::forClassroom($this->classroom1->id)->get();

    expect($classroom1Assignments)->toHaveCount(2)
        ->and($classroom1Assignments->every(fn($a) => $a->classroom_id === $this->classroom1->id))->toBeTrue();
});

test('can scope assignments by session and term', function () {
    $oldSession = Session::factory()->create([
        'is_active' => false,
        'start_year' => 2025,
        'end_year' => 2026,
    ]);

    $oldTerm = Term::factory()->create([
        'session_id' => $oldSession->id,
        'is_active' => false,
        'name' => 'First Term',
        'order' => 1,
    ]);

    // Current session assignment
    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    // Old session assignment
    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $oldSession->id,
        'term_id' => $oldTerm->id,
    ]);

    $currentAssignments = TeacherSubjectAssignment::forSession($this->session->id)
        ->forTerm($this->term->id)
        ->get();

    expect($currentAssignments)->toHaveCount(1)
        ->and($currentAssignments->first()->session_id)->toBe($this->session->id);
});

test('classroom has assignments relationship', function () {
    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher2->id,
        'subject_id' => $this->subject2->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    $classroom = Classroom::with('assignments')->find($this->classroom1->id);

    expect($classroom->assignments)->toHaveCount(2);
});

test('can count assignments per classroom', function () {
    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher2->id,
        'subject_id' => $this->subject2->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    $classrooms = Classroom::withCount([
        'assignments' => function ($query) {
            $query->where('session_id', $this->session->id)
                  ->where('term_id', $this->term->id);
        }
    ])->get();

    $classroom1 = $classrooms->firstWhere('id', $this->classroom1->id);

    expect($classroom1->assignments_count)->toBe(2);
});

test('prevents duplicate assignments', function () {
    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    expect(function () {
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher1->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroom1->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

test('can delete assignment', function () {
    $assignment = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    $assignment->delete();

    expect(TeacherSubjectAssignment::count())->toBe(0);
});

test('only active teachers can be assigned', function () {
    $inactiveTeacher = User::factory()->create([
        'role' => 'teacher',
        'is_active' => false,
    ]);

    $activeTeachers = User::activeTeachers()->get();

    expect($activeTeachers->contains($this->teacher1))->toBeTrue()
        ->and($activeTeachers->contains($inactiveTeacher))->toBeFalse();
});

// ──────────────────────────────────────────────────────────────
// Assignment Rules
// ──────────────────────────────────────────────────────────────

describe('Assignment Rules', function () {

    it('allows a teacher to teach more than one subject', function () {
        $assignment1 = TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher1->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroom1->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        $assignment2 = TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher1->id,
            'subject_id' => $this->subject2->id,
            'classroom_id' => $this->classroom1->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        $teacherAssignments = TeacherSubjectAssignment::forTeacher($this->teacher1->id)->get();

        expect($teacherAssignments)->toHaveCount(2)
            ->and($assignment1->subject_id)->toBe($this->subject1->id)
            ->and($assignment2->subject_id)->toBe($this->subject2->id);
    });

    it('allows a subject to be taught by different teachers in different classes', function () {
        $assignment1 = TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher1->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroom1->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        $assignment2 = TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher2->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroom2->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        expect($assignment1->id)->not->toBe($assignment2->id)
            ->and($assignment1->subject_id)->toBe($assignment2->subject_id)
            ->and($assignment1->teacher_id)->not->toBe($assignment2->teacher_id)
            ->and($assignment1->classroom_id)->not->toBe($assignment2->classroom_id);
    });

    it('prevents two teachers from teaching the same subject in the same class', function () {
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher1->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroom1->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        expect(fn () => TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher2->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroom1->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('allows a teacher to teach the same subject in multiple classes', function () {
        $assignment1 = TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher1->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroom1->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        $assignment2 = TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher1->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroom2->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        expect($assignment1->id)->not->toBe($assignment2->id)
            ->and($assignment1->classroom_id)->not->toBe($assignment2->classroom_id);
    });
});
