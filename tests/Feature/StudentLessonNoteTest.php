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
    $fileName = $overrides['file_name'] ?? 'test.pdf';
    $title = $overrides['title'] ?? null;
    unset($overrides['file_name'], $overrides['title']);

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
        'file_path' => "lesson-notes/{$fileName}",
        'file_name' => $fileName,
        'title' => $title,
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

it('allows student to retrieve approved notes even when classroom_subject pivot table is empty', function () {
    // Detach all subjects from classroom 1 to simulate production state
    $this->classroom1->subjects()->detach();

    // Create an approved note for classroom 1
    $note = createTestNote(['status' => 'approved']);

    // Authenticate as Student 1 (enrolled in classroom 1)
    $this->actingAs($this->studentUser1);

    $results = LessonNoteResource::getEloquentQuery()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($note->id);

    // Verify policy allows view
    expect($this->studentUser1->can('view', $note))->toBeTrue();
});

it('denies student viewing unapproved notes via policy', function () {
    $pendingNote = createTestNote(['status' => 'pending']);
    $rejectedNote = createTestNote(['status' => 'rejected', 'week_number' => 2]);

    $this->actingAs($this->studentUser1);

    expect($this->studentUser1->can('view', $pendingNote))->toBeFalse()
        ->and($this->studentUser1->can('view', $rejectedNote))->toBeFalse();
});

it('denies student viewing approved notes of a different classroom via policy', function () {
    $noteClassroom2 = createTestNote([
        'classroom_id' => $this->classroom2->id,
        'subject_id' => $this->subject2->id,
        'status' => 'approved',
    ]);

    $this->actingAs($this->studentUser1);

    expect($this->studentUser1->can('view', $noteClassroom2))->toBeFalse();
});

it('shows only available approved weeks per subject on the student page', function () {
    // Note for Week 1: Approved
    $noteWeek1 = createTestNote([
        'week_number' => 1,
        'status' => 'approved',
        'file_name' => 'Week 1 Approved Topic.pdf',
    ]);

    // Note for Week 2: Rejected (MUST NOT SHOW)
    $noteWeek2 = createTestNote([
        'week_number' => 2,
        'status' => 'rejected',
        'file_name' => 'Week 2 Rejected Topic.pdf',
    ]);

    // Note for Week 3: Pending (MUST NOT SHOW)
    $noteWeek3 = createTestNote([
        'week_number' => 3,
        'status' => 'pending',
        'file_name' => 'Week 3 Pending Topic.pdf',
    ]);

    // Note for Week 5: Approved
    $noteWeek5 = createTestNote([
        'week_number' => 5,
        'status' => 'approved',
        'file_name' => 'Week 5 Approved Topic.pdf',
    ]);

    $this->actingAs($this->studentUser1);

    \Livewire\Livewire::test(\App\Filament\Student\Resources\LessonNoteResource\Pages\ListLessonNotes::class)
        ->call('selectSubject', $this->subject1->id)
        ->assertSee('Week 1 Approved Topic.pdf')
        ->assertSee('Week 5 Approved Topic.pdf')
        ->assertDontSee('Week 2 Rejected Topic.pdf')
        ->assertDontSee('Week 3 Pending Topic.pdf');
});

it('forbids students from downloading or viewing lesson plans', function () {
    $note = createTestNote(['status' => 'approved']);

    $plan = \App\Models\LessonPlan::create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'week_number' => 1,
        'status' => 'approved',
    ]);

    $this->actingAs($this->studentUser1);

    // Direct attempt to download lesson plan PDF via controller
    $response = $this->get(route('admin.lesson-plan.pdf', $plan));
    $response->assertForbidden();
});

it('opens modal only for approved notes belonging to student classroom', function () {
    $approvedNote = createTestNote([
        'week_number' => 1,
        'status' => 'approved',
        'file_name' => 'Week1.pdf',
    ]);

    $pendingNote = createTestNote([
        'week_number' => 2,
        'status' => 'pending',
        'file_name' => 'Week2.pdf',
    ]);

    $otherClassNote = createTestNote([
        'classroom_id' => $this->classroom2->id,
        'subject_id' => $this->subject2->id,
        'week_number' => 1,
        'status' => 'approved',
        'file_name' => 'OtherClassWeek1.pdf',
    ]);

    $this->actingAs($this->studentUser1);

    \Livewire\Livewire::test(\App\Filament\Student\Resources\LessonNoteResource\Pages\ListLessonNotes::class)
        // 1. Try viewing pending note -> rejected
        ->call('viewNote', $pendingNote->id)
        ->assertSet('showViewModal', false)
        ->assertSet('viewingNoteId', null)
        // 2. Try viewing note from other classroom -> rejected
        ->call('viewNote', $otherClassNote->id)
        ->assertSet('showViewModal', false)
        ->assertSet('viewingNoteId', null)
        // 3. View approved note from student classroom -> success!
        ->call('viewNote', $approvedNote->id)
        ->assertSet('showViewModal', true)
        ->assertSet('viewingNoteId', $approvedNote->id)
        ->assertSee('Week1.pdf')
        // 4. Close modal
        ->call('closeViewModal')
        ->assertSet('showViewModal', false);
});
