<?php

use App\Models\Classroom;
use App\Models\LessonNote;
use App\Models\LessonNoteVersion;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\User;
use App\Filament\Student\Resources\LessonNoteResource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 1. Create active session and term
    $this->session = Session::createWithTerms(2026);
    $this->session->activate();
    $this->term = $this->session->terms()->where('order', 1)->first();
    $this->term->update(['is_active' => true]);

    // 2. Create Classrooms
    $this->classroom1 = Classroom::create(['name' => 'Primary 1', 'class_order' => 1]);
    $this->classroom2 = Classroom::create(['name' => 'Primary 2', 'class_order' => 2]);

    // 3. Create Subjects
    $this->subject1 = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
    $this->subject2 = Subject::create(['name' => 'English Language', 'code' => 'ENG']);

    // Associate subjects with classrooms
    $this->classroom1->subjects()->attach($this->subject1->id);
    $this->classroom2->subjects()->attach($this->subject2->id);

    // 4. Create Students & Users
    $this->studentUser1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $this->student1 = Student::create([
        'user_id' => $this->studentUser1->id,
        'full_name' => 'Student One',
        'status' => 'active',
        'is_portal_active' => true,
    ]);
    StudentEnrollment::create([
        'student_id' => $this->student1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
    ]);

    $this->studentUser2 = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $this->student2 = Student::create([
        'user_id' => $this->studentUser2->id,
        'full_name' => 'Student Two',
        'status' => 'active',
        'is_portal_active' => true,
    ]);
    StudentEnrollment::create([
        'student_id' => $this->student2->id,
        'classroom_id' => $this->classroom2->id,
        'session_id' => $this->session->id,
    ]);

    $this->teacher = User::factory()->create(['role' => 'teacher']);
});

function createTestNote(array $overrides = []): LessonNote
{
    $note = LessonNote::create(array_merge([
        'teacher_id' => test()->teacher->id,
        'subject_id' => test()->subject1->id,
        'classroom_id' => test()->classroom1->id,
        'session_id' => test()->session->id,
        'term_id' => test()->term->id,
        'week_number' => 1,
        'status' => 'pending',
    ], $overrides));

    $version = LessonNoteVersion::create([
        'lesson_note_id' => $note->id,
        'file_path' => "lesson-notes/test.pdf",
        'file_name' => 'test.pdf',
        'file_size' => 1024,
        'file_hash' => 'hash',
        'uploaded_by' => test()->teacher->id,
        'status' => $note->status,
    ]);

    $note->update(['latest_version_id' => $version->id]);

    return $note;
}

it('allows student to retrieve approved notes for their classroom and offered subjects', function () {
    // Note 1: Approved, correct classroom, correct subject, active session/term
    $note1 = createTestNote(['status' => 'approved']);

    // Authenticate as Student 1
    $this->actingAs($this->studentUser1);

    $results = LessonNoteResource::getEloquentQuery()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($note1->id);
});

it('hides pending and rejected notes from students', function () {
    // Note 1: Pending
    createTestNote(['status' => 'pending']);

    // Note 2: Rejected
    createTestNote(['status' => 'rejected', 'week_number' => 2]);

    // Authenticate as Student 1
    $this->actingAs($this->studentUser1);

    $results = LessonNoteResource::getEloquentQuery()->get();

    expect($results)->toBeEmpty();
});

it('hides notes from different classrooms', function () {
    // Note for Classroom 2 (Primary 2)
    $note = createTestNote([
        'classroom_id' => $this->classroom2->id,
        'subject_id' => $this->subject2->id,
        'status' => 'approved'
    ]);

    // Authenticate as Student 1 (enrolled in Primary 1)
    $this->actingAs($this->studentUser1);

    $results = LessonNoteResource::getEloquentQuery()->get();

    expect($results)->toBeEmpty();
});

it('hides approved notes from inactive sessions/terms', function () {
    // Create inactive session/term
    $oldSession = Session::createWithTerms(2025);
    $oldTerm = $oldSession->terms()->first();

    $note = createTestNote([
        'session_id' => $oldSession->id,
        'term_id' => $oldTerm->id,
        'status' => 'approved'
    ]);

    // Authenticate as Student 1
    $this->actingAs($this->studentUser1);

    $results = LessonNoteResource::getEloquentQuery()->get();

    expect($results)->toBeEmpty();
});
