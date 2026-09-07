<?php

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use App\Filament\Teacher\Pages\SubjectRequests;
use App\Models\Classroom;
use App\Models\LessonNote;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use App\Policies\ScorePolicy;
use App\Services\LessonNoteCache;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Admin user
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
        'name' => 'School Admin',
    ]);

    // Active session and term
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

    // Teachers
    $this->teacher1 = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
        'name' => 'Mr. Johnson',
    ]);

    $this->teacher2 = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
        'name' => 'Mrs. Smith',
    ]);

    // Classrooms
    $this->classroom1 = Classroom::factory()->create(['name' => 'JSS 1A']);
    $this->classroom2 = Classroom::factory()->create(['name' => 'JSS 1B']);
    $this->classroom3 = Classroom::factory()->create(['name' => 'JSS 2A']);

    // Subjects
    $this->subjectMath = Subject::factory()->create(['name' => 'Mathematics']);
    $this->subjectEng = Subject::factory()->create(['name' => 'English Language']);
});

test('teacher can view subject selection page', function () {
    $this->actingAs($this->teacher1);

    Livewire::test(SubjectRequests::class)
        ->assertSuccessful();
});

test('teacher can submit subject request for multiple classes and admin receives notification', function () {
    $this->actingAs($this->teacher1);

    Livewire::test(SubjectRequests::class)
        ->fillForm([
            'subject_id' => $this->subjectMath->id,
            'classroom_ids' => [$this->classroom1->id, $this->classroom2->id],
        ])
        ->call('submitRequest')
        ->assertHasNoFormErrors();

    // Verify database records are created with 'pending' status
    expect(TeacherSubjectAssignment::count())->toBe(2);

    $assignments = TeacherSubjectAssignment::where('teacher_id', $this->teacher1->id)->get();
    expect($assignments)->toHaveCount(2)
        ->and($assignments->every(fn ($a) => $a->status === 'pending'))->toBeTrue()
        ->and($assignments->pluck('classroom_id')->toArray())->toEqualCanonicalizing([$this->classroom1->id, $this->classroom2->id])
        ->and($assignments->every(fn ($a) => $a->subject_id === $this->subjectMath->id))->toBeTrue();

    // Verify admin received database notification
    $notifications = DatabaseNotification::where('notifiable_id', $this->admin->id)->get();
    expect($notifications)->not->toBeEmpty();

    $notification = $notifications->first();
    expect($notification->data['title'])->toContain('New Subject Assignment Request')
        ->and($notification->data['body'])->toContain($this->teacher1->name)
        ->and($notification->data['body'])->toContain($this->subjectMath->name);
});

test('teacher request skips classes that already have an approved assignment for that subject', function () {
    // Classroom 1 already has Mathematics assigned to Teacher 2
    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher2->id,
        'subject_id' => $this->subjectMath->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'approved',
    ]);

    $this->actingAs($this->teacher1);

    // Teacher 1 attempts to request Mathematics for Classroom 1 and Classroom 2
    Livewire::test(SubjectRequests::class)
        ->fillForm([
            'subject_id' => $this->subjectMath->id,
            'classroom_ids' => [$this->classroom1->id, $this->classroom2->id],
        ])
        ->call('submitRequest');

    // Only Classroom 2 should be created for Teacher 1
    $teacher1Assignments = TeacherSubjectAssignment::where('teacher_id', $this->teacher1->id)->get();
    expect($teacher1Assignments)->toHaveCount(1)
        ->and($teacher1Assignments->first()->classroom_id)->toBe($this->classroom2->id)
        ->and($teacher1Assignments->first()->status)->toBe('pending');
});

test('teacher can see their requests table and cancel pending requests', function () {
    $this->actingAs($this->teacher1);

    $assignment = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subjectMath->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'pending',
    ]);

    Livewire::test(SubjectRequests::class)
        ->assertCanSeeTableRecords([$assignment])
        ->callTableAction('cancel', $assignment->id);

    expect(TeacherSubjectAssignment::find($assignment->id))->toBeNull();
});

test('admin can approve pending request and teacher receives approval notification', function () {
    $pending = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subjectMath->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TeacherSubjectAssignmentResource\Pages\ListTeacherSubjectAssignments::class)
        ->callTableAction('approve', $pending->id);

    $fresh = $pending->fresh();
    expect($fresh->status)->toBe('approved')
        ->and($fresh->approved_by)->toBe($this->admin->id)
        ->and($fresh->approved_at)->not->toBeNull();

    // Teacher should receive database notification
    $teacherNotifications = DatabaseNotification::where('notifiable_id', $this->teacher1->id)->get();
    expect($teacherNotifications)->not->toBeEmpty();
    expect($teacherNotifications->first()->data['title'])->toContain('Subject Assignment Approved');
});

test('admin can reject pending request with reason and teacher receives rejection notification', function () {
    $pending = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subjectMath->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TeacherSubjectAssignmentResource\Pages\ListTeacherSubjectAssignments::class)
        ->callTableAction('reject', $pending->id, data: [
            'rejection_reason' => 'Classroom schedule is full',
        ]);

    $fresh = $pending->fresh();
    expect($fresh->status)->toBe('rejected')
        ->and($fresh->approved_by)->toBe($this->admin->id)
        ->and($fresh->rejection_reason)->toBe('Classroom schedule is full');

    // Teacher should receive database notification with reason
    $teacherNotifications = DatabaseNotification::where('notifiable_id', $this->teacher1->id)->get();
    expect($teacherNotifications)->not->toBeEmpty();
    expect($teacherNotifications->first()->data['title'])->toContain('Subject Assignment Rejected')
        ->and($teacherNotifications->first()->data['body'])->toContain('Classroom schedule is full');
});

test('admin can bulk approve pending requests', function () {
    $pending1 = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subjectMath->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'pending',
    ]);

    $pending2 = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subjectEng->id,
        'classroom_id' => $this->classroom2->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TeacherSubjectAssignmentResource\Pages\ListTeacherSubjectAssignments::class)
        ->callTableBulkAction('bulk_approve', [$pending1, $pending2]);

    expect($pending1->fresh()->status)->toBe('approved')
        ->and($pending2->fresh()->status)->toBe('approved');
});

test('admin can bulk reject pending requests', function () {
    $pending1 = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subjectMath->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'pending',
    ]);

    $pending2 = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subjectEng->id,
        'classroom_id' => $this->classroom2->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TeacherSubjectAssignmentResource\Pages\ListTeacherSubjectAssignments::class)
        ->callTableBulkAction('bulk_reject', [$pending1, $pending2]);

    expect($pending1->fresh()->status)->toBe('rejected')
        ->and($pending2->fresh()->status)->toBe('rejected');
});

test('pending and rejected assignments do NOT grant teaching permissions', function () {
    $pending = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher1->id,
        'subject_id' => $this->subjectMath->id,
        'classroom_id' => $this->classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'pending',
    ]);

    $rejected = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher2->id,
        'subject_id' => $this->subjectEng->id,
        'classroom_id' => $this->classroom2->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'status' => 'rejected',
    ]);

    // isAssigned must be false for pending and rejected
    expect(TeacherSubjectAssignment::isAssigned(
        $this->teacher1->id,
        $this->subjectMath->id,
        $this->classroom1->id,
        $this->session->id,
        $this->term->id
    ))->toBeFalse();

    expect(TeacherSubjectAssignment::isAssigned(
        $this->teacher2->id,
        $this->subjectEng->id,
        $this->classroom2->id,
        $this->session->id,
        $this->term->id
    ))->toBeFalse();

    // scopeActive must exclude pending and rejected
    $activeAssignments = TeacherSubjectAssignment::active()->get();
    expect($activeAssignments->contains($pending))->toBeFalse()
        ->and($activeAssignments->contains($rejected))->toBeFalse();

    // ScorePolicy enterScore must return false for pending teacher
    $scorePolicy = new ScorePolicy();
    $canEnter = $scorePolicy->enterScore(
        $this->teacher1,
        $this->subjectMath->id,
        $this->classroom1->id,
        $this->session->id,
        $this->term->id
    );
    expect($canEnter)->toBeFalse();

    // LessonNoteCache must exclude pending teacher assignments
    $cache = app(LessonNoteCache::class);
    $assignments = $cache->getTeacherAssignments($this->teacher1->id);
    expect($assignments)->toHaveCount(0);
});
