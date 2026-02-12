<?php

use App\Models\ClassTeacherAssignment;
use App\Models\Classroom;
use App\Models\LessonNote;
use App\Models\Session;
use App\Models\Subject;
use App\Models\SubmissionWindow;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Policies\LessonNotePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Setup
// ──────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->session = Session::createWithTerms(2026);
    $this->session->activate();
    $this->term = $this->session->terms()->where('order', 1)->first();
    $this->term->update(['is_active' => true]);

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->classTeacher = User::factory()->create(['role' => 'teacher', 'name' => 'Class Teacher']);
    $this->subjectTeacher = User::factory()->create(['role' => 'teacher', 'name' => 'Subject Teacher']);
    $this->hybridTeacher = User::factory()->create(['role' => 'teacher', 'name' => 'Hybrid Teacher']);
    $this->unassignedTeacher = User::factory()->create(['role' => 'teacher', 'name' => 'Unassigned Teacher']);

    $this->subject1 = Subject::create([
        'name' => 'Mathematics',
        'code' => 'MATH',
        'description' => 'Mathematics',
    ]);

    $this->subject2 = Subject::create([
        'name' => 'English',
        'code' => 'ENG',
        'description' => 'English Language',
    ]);

    $this->classroomA = Classroom::create([
        'name' => 'JSS 1A',
        'level' => 'JSS 1',
        'section' => 'A',
    ]);

    $this->classroomB = Classroom::create([
        'name' => 'JSS 1B',
        'level' => 'JSS 1',
        'section' => 'B',
    ]);

    // Create submission window
    SubmissionWindow::create([
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'week_number' => 1,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addDay(),
        'is_open' => true,
    ]);
});

// ──────────────────────────────────────────────────────────────
// 1. Database Tests
// ──────────────────────────────────────────────────────────────

describe('Database', function () {

    it('creates a class teacher assignment', function () {
        $assignment = ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        expect($assignment)->toBeInstanceOf(ClassTeacherAssignment::class)
            ->and($assignment->teacher_id)->toBe($this->classTeacher->id)
            ->and($assignment->class_id)->toBe($this->classroomA->id)
            ->and($assignment->session_id)->toBe($this->session->id);
    });

    it('enforces unique constraint - one class teacher per class per session', function () {
        ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        expect(fn () => ClassTeacherAssignment::create([
            'teacher_id' => $this->subjectTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('allows a teacher to be class teacher for multiple classes', function () {
        $assignment1 = ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        $assignment2 = ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomB->id,
            'session_id' => $this->session->id,
        ]);

        expect($assignment1->id)->not->toBe($assignment2->id);
    });

    it('has correct relationships', function () {
        $assignment = ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        expect($assignment->teacher->id)->toBe($this->classTeacher->id)
            ->and($assignment->classroom->id)->toBe($this->classroomA->id)
            ->and($assignment->session->id)->toBe($this->session->id);
    });

    it('scopes assignments for a specific teacher', function () {
        ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        ClassTeacherAssignment::create([
            'teacher_id' => $this->subjectTeacher->id,
            'class_id' => $this->classroomB->id,
            'session_id' => $this->session->id,
        ]);

        $assignments = ClassTeacherAssignment::forTeacher($this->classTeacher->id)->get();

        expect($assignments)->toHaveCount(1)
            ->and($assignments->first()->teacher_id)->toBe($this->classTeacher->id);
    });

    it('scopes active assignments', function () {
        ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        $oldSession = Session::createWithTerms(2020);
        ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomB->id,
            'session_id' => $oldSession->id,
        ]);

        $active = ClassTeacherAssignment::active()->get();

        expect($active)->toHaveCount(1)
            ->and($active->first()->session_id)->toBe($this->session->id);
    });

    it('checks if teacher is class teacher', function () {
        ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        expect(ClassTeacherAssignment::isClassTeacher(
            $this->classTeacher->id,
            $this->classroomA->id,
            $this->session->id
        ))->toBeTrue();

        expect(ClassTeacherAssignment::isClassTeacher(
            $this->classTeacher->id,
            $this->classroomB->id,
            $this->session->id
        ))->toBeFalse();

        expect(ClassTeacherAssignment::isClassTeacher(
            $this->subjectTeacher->id,
            $this->classroomA->id,
            $this->session->id
        ))->toBeFalse();
    });

});

// ──────────────────────────────────────────────────────────────
// 2. Authorization Tests
// ──────────────────────────────────────────────────────────────

describe('Authorization', function () {

    it('allows class teacher to upload lesson notes for any subject in their class', function () {
        ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        expect(LessonNotePolicy::canUploadFor(
            $this->classTeacher,
            $this->subject1->id,
            $this->classroomA->id,
            $this->session->id,
            $this->term->id
        ))->toBeTrue();

        expect(LessonNotePolicy::canUploadFor(
            $this->classTeacher,
            $this->subject2->id,
            $this->classroomA->id,
            $this->session->id,
            $this->term->id
        ))->toBeTrue();
    });

    it('prevents class teacher from uploading for classes they do not manage', function () {
        ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        expect(LessonNotePolicy::canUploadFor(
            $this->classTeacher,
            $this->subject1->id,
            $this->classroomB->id,
            $this->session->id,
            $this->term->id
        ))->toBeFalse();
    });

    it('allows class teacher to view all lesson notes in their class', function () {
        ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        $note = LessonNote::create([
            'teacher_id' => $this->subjectTeacher->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 1,
            'status' => 'pending',
        ]);

        expect($this->classTeacher->can('view', $note))->toBeTrue();
    });

    it('prevents class teacher from viewing notes in other classes', function () {
        ClassTeacherAssignment::create([
            'teacher_id' => $this->classTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        $note = LessonNote::create([
            'teacher_id' => $this->subjectTeacher->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroomB->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 1,
            'status' => 'pending',
        ]);

        expect($this->classTeacher->can('view', $note))->toBeFalse();
    });

    it('subject teacher permissions still work unchanged', function () {
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->subjectTeacher->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        expect(LessonNotePolicy::canUploadFor(
            $this->subjectTeacher,
            $this->subject1->id,
            $this->classroomA->id,
            $this->session->id,
            $this->term->id
        ))->toBeTrue();

        expect(LessonNotePolicy::canUploadFor(
            $this->subjectTeacher,
            $this->subject2->id,
            $this->classroomA->id,
            $this->session->id,
            $this->term->id
        ))->toBeFalse();
    });

    it('hybrid scenario - teacher with both class teacher and subject teacher roles', function () {
        // Hybrid teacher is class teacher for Classroom A
        ClassTeacherAssignment::create([
            'teacher_id' => $this->hybridTeacher->id,
            'class_id' => $this->classroomA->id,
            'session_id' => $this->session->id,
        ]);

        // Hybrid teacher is subject teacher for Math in Classroom B
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->hybridTeacher->id,
            'subject_id' => $this->subject1->id,
            'classroom_id' => $this->classroomB->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        // Can upload any subject in Classroom A (class teacher)
        expect(LessonNotePolicy::canUploadFor(
            $this->hybridTeacher,
            $this->subject1->id,
            $this->classroomA->id,
            $this->session->id,
            $this->term->id
        ))->toBeTrue();

        expect(LessonNotePolicy::canUploadFor(
            $this->hybridTeacher,
            $this->subject2->id,
            $this->classroomA->id,
            $this->session->id,
            $this->term->id
        ))->toBeTrue();

        // Can upload only Math in Classroom B (subject teacher)
        expect(LessonNotePolicy::canUploadFor(
            $this->hybridTeacher,
            $this->subject1->id,
            $this->classroomB->id,
            $this->session->id,
            $this->term->id
        ))->toBeTrue();

        expect(LessonNotePolicy::canUploadFor(
            $this->hybridTeacher,
            $this->subject2->id,
            $this->classroomB->id,
            $this->session->id,
            $this->term->id
        ))->toBeFalse();
    });

    it('unassigned teacher cannot upload anywhere', function () {
        expect(LessonNotePolicy::canUploadFor(
            $this->unassignedTeacher,
            $this->subject1->id,
            $this->classroomA->id,
            $this->session->id,
            $this->term->id
        ))->toBeFalse();
    });

});
