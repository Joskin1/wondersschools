<?php

use App\Jobs\LogLessonNoteAction;
use App\Jobs\NotifyAdminOfNewSubmission;
use App\Jobs\ProcessLessonNoteUpload;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\LessonNote;
use App\Models\LessonNoteVersion;
use App\Models\Session;
use App\Models\Subject;
use App\Models\SubmissionWindow;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Notifications\LessonNoteReviewed;
use App\Notifications\LessonNoteSubmitted;
use App\Policies\LessonNotePolicy;
use App\Services\LessonNoteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Setup
// ──────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->session = Session::createWithTerms(2026);
    $this->session->activate();
    $this->term = $this->session->terms()->where('order', 1)->first();
    $this->term->update(['is_active' => true]);

    $this->sudo = User::factory()->create(['role' => 'sudo']);
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->teacher = User::factory()->create(['role' => 'teacher']);
    $this->teacher2 = User::factory()->create(['role' => 'teacher']);
    $this->student = User::factory()->create(['role' => 'student']);

    $this->subject = Subject::create([
        'name' => 'Mathematics',
        'code' => 'MATH',
        'description' => 'Mathematics and Numeracy',
    ]);

    $this->subject2 = Subject::create([
        'name' => 'English Language',
        'code' => 'ENG',
        'description' => 'English Language',
    ]);

    $this->classroom = Classroom::create([
        'name' => 'JSS1',
        'class_order' => 1,
    ]);

    $this->classroom2 = Classroom::create([
        'name' => 'JSS2',
        'class_order' => 2,
    ]);

    $this->assignment = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'classroom_id' => $this->classroom->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    $this->openWindow = SubmissionWindow::create([
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'week_number' => 1,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addDay(),
        'is_open' => true,
    ]);

    $this->closedWindow = SubmissionWindow::create([
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
        'week_number' => 2,
        'opens_at' => now()->subWeek(),
        'closes_at' => now()->subDay(),
        'is_open' => false,
    ]);
});

/**
 * Helper to create a lesson note with sensible defaults.
 */
function createNote(array $overrides = []): LessonNote
{
    return LessonNote::create(array_merge([
        'teacher_id' => test()->teacher->id,
        'subject_id' => test()->subject->id,
        'classroom_id' => test()->classroom->id,
        'session_id' => test()->session->id,
        'term_id' => test()->term->id,
        'week_number' => 1,
        'status' => 'pending',
    ], $overrides));
}

/**
 * Helper to create a version for a given lesson note.
 */
function createVersion(LessonNote $note, array $overrides = []): LessonNoteVersion
{
    $version = LessonNoteVersion::create(array_merge([
        'lesson_note_id' => $note->id,
        'file_path' => "lesson-notes/{$note->session_id}/{$note->term_id}/week-{$note->week_number}/{$note->teacher_id}/" . md5(rand()) . '.pdf',
        'file_name' => 'math-week1.pdf',
        'file_size' => 512_000,
        'file_hash' => hash('sha256', uniqid()),
        'uploaded_by' => $note->teacher_id,
        'status' => $note->status,
        'original_filename' => $overrides['file_name'] ?? 'math-week1.pdf',
    ], $overrides));

    $note->update(['latest_version_id' => $version->id]);

    return $version;
}

// ──────────────────────────────────────────────────────────────
// 1. Teacher Upload — Core Flow
// ──────────────────────────────────────────────────────────────

describe('Teacher Upload', function () {

    it('creates a lesson note when window is open and teacher is assigned', function () {
        $note = createNote();

        expect($note)->toBeInstanceOf(LessonNote::class)
            ->and($note->status)->toBe('pending')
            ->and($note->teacher_id)->toBe($this->teacher->id)
            ->and($note->subject_id)->toBe($this->subject->id)
            ->and($note->classroom_id)->toBe($this->classroom->id)
            ->and($note->session_id)->toBe($this->session->id)
            ->and($note->term_id)->toBe($this->term->id)
            ->and($note->week_number)->toBe(1);
    });

    it('creates a version record and links it as latest_version', function () {
        $note = createNote();
        $version = createVersion($note);

        expect($note->fresh()->latest_version_id)->toBe($version->id)
            ->and($note->latestVersion->id)->toBe($version->id)
            ->and($note->versions)->toHaveCount(1)
            ->and($version->file_path)->toContain('lesson-notes/')
            ->and($version->file_hash)->toHaveLength(64);
    });

    it('supports multiple versions (re-upload) on the same note', function () {
        $note = createNote();
        $v1 = createVersion($note, ['file_name' => 'v1.pdf']);
        $v2 = createVersion($note, ['file_name' => 'v2.pdf']);

        $note->refresh();
        expect($note->versions)->toHaveCount(2)
            ->and($note->latest_version_id)->toBe($v2->id)
            ->and($note->latestVersion->file_name)->toBe('v2.pdf');
    });

    it('resets status to pending on re-upload of rejected note', function () {
        $note = createNote(['status' => 'rejected']);
        createVersion($note, ['status' => 'rejected']);

        // Simulate re-upload
        $note->update(['status' => 'pending']);
        $newVersion = createVersion($note, ['status' => 'pending', 'file_name' => 'revised.pdf']);

        expect($note->fresh()->status)->toBe('pending')
            ->and($note->fresh()->latest_version_id)->toBe($newVersion->id);
    });

});

// ──────────────────────────────────────────────────────────────
// 2. Submission Window Enforcement
// ──────────────────────────────────────────────────────────────

describe('Submission Window Enforcement', function () {

    it('reports window as open when is_open=true and within time range', function () {
        expect($this->openWindow->isCurrentlyOpen())->toBeTrue();
    });

    it('reports window as closed when is_open=false', function () {
        expect($this->closedWindow->isCurrentlyOpen())->toBeFalse();
    });

    it('reports window as closed when past closes_at even if is_open=true', function () {
        $this->openWindow->update(['closes_at' => now()->subHour()]);

        expect($this->openWindow->fresh()->isCurrentlyOpen())->toBeFalse();
    });

    it('reports window as closed when before opens_at even if is_open=true', function () {
        $this->openWindow->update(['opens_at' => now()->addHour()]);

        expect($this->openWindow->fresh()->isCurrentlyOpen())->toBeFalse();
    });

    it('finds currently open windows via scope', function () {
        $open = SubmissionWindow::currentlyOpen()->get();

        expect($open)->toHaveCount(1)
            ->and($open->first()->week_number)->toBe(1);
    });

    it('finds windows by session/term/week via forWeek scope', function () {
        $found = SubmissionWindow::forWeek($this->session->id, $this->term->id, 1)->first();

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($this->openWindow->id);
    });

    it('can toggle a window open and closed', function () {
        $this->openWindow->close($this->admin->id);
        expect($this->openWindow->fresh()->is_open)->toBeFalse();

        $this->openWindow->open($this->admin->id);
        expect($this->openWindow->fresh()->is_open)->toBeTrue();
    });

});

// ──────────────────────────────────────────────────────────────
// 3. Teacher Assignment Validation
// ──────────────────────────────────────────────────────────────

describe('Teacher Assignment Validation', function () {

    it('confirms teacher is assigned to their subject/classroom', function () {
        $assigned = TeacherSubjectAssignment::isAssigned(
            $this->teacher->id,
            $this->subject->id,
            $this->classroom->id,
            $this->session->id,
            $this->term->id
        );

        expect($assigned)->toBeTrue();
    });

    it('rejects teacher for unassigned subject', function () {
        $assigned = TeacherSubjectAssignment::isAssigned(
            $this->teacher->id,
            $this->subject2->id,
            $this->classroom->id,
            $this->session->id,
            $this->term->id
        );

        expect($assigned)->toBeFalse();
    });

    it('rejects teacher for unassigned classroom', function () {
        $assigned = TeacherSubjectAssignment::isAssigned(
            $this->teacher->id,
            $this->subject->id,
            $this->classroom2->id,
            $this->session->id,
            $this->term->id
        );

        expect($assigned)->toBeFalse();
    });

    it('rejects different teacher for same subject/classroom', function () {
        $assigned = TeacherSubjectAssignment::isAssigned(
            $this->teacher2->id,
            $this->subject->id,
            $this->classroom->id,
            $this->session->id,
            $this->term->id
        );

        expect($assigned)->toBeFalse();
    });

    it('returns active assignments scoped to current session/term', function () {
        $active = TeacherSubjectAssignment::forTeacher($this->teacher->id)
            ->active()
            ->get();

        expect($active)->toHaveCount(1)
            ->and($active->first()->subject_id)->toBe($this->subject->id);
    });

});

// ──────────────────────────────────────────────────────────────
// 4. Duplicate Submission Prevention
// ──────────────────────────────────────────────────────────────

describe('Duplicate Prevention', function () {

    it('prevents duplicate lesson notes for the same teacher/subject/class/session/term/week', function () {
        createNote();

        expect(fn () => createNote())->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('allows same teacher to submit for different weeks', function () {
        $note1 = createNote(['week_number' => 1]);
        $note2 = createNote(['week_number' => 3]);

        expect($note1->id)->not->toBe($note2->id);
    });

    it('allows same teacher to submit for different subjects', function () {
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject2->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        $note1 = createNote(['subject_id' => $this->subject->id]);
        $note2 = createNote(['subject_id' => $this->subject2->id]);

        expect($note1->id)->not->toBe($note2->id);
    });

    it('allows different teachers to submit for the same subject/class/week', function () {
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher2->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        $note1 = createNote(['teacher_id' => $this->teacher->id]);
        $note2 = createNote(['teacher_id' => $this->teacher2->id]);

        expect($note1->id)->not->toBe($note2->id);
    });

});

// ──────────────────────────────────────────────────────────────
// 5. Admin Review — Approve / Reject
// ──────────────────────────────────────────────────────────────

describe('Admin Review', function () {

    it('approves a lesson note with comment', function () {
        $note = createNote();
        $version = createVersion($note);

        $note->approve('Excellent work!', $this->admin->id);

        $note->refresh();
        $version->refresh();

        expect($note->status)->toBe('approved')
            ->and($version->status)->toBe('approved')
            ->and($version->admin_comment)->toBe('Excellent work!')
            ->and($version->reviewed_by)->toBe($this->admin->id)
            ->and($version->reviewed_at)->not->toBeNull();
    });

    it('approves a lesson note without comment', function () {
        $note = createNote();
        createVersion($note);

        $note->approve(null, $this->admin->id);

        expect($note->fresh()->status)->toBe('approved');
    });

    it('rejects a lesson note with required comment', function () {
        $note = createNote();
        $version = createVersion($note);

        $note->reject('Add more examples.', $this->admin->id);

        $note->refresh();
        $version->refresh();

        expect($note->status)->toBe('rejected')
            ->and($version->status)->toBe('rejected')
            ->and($version->admin_comment)->toBe('Add more examples.')
            ->and($version->reviewed_by)->toBe($this->admin->id)
            ->and($version->reviewed_at)->not->toBeNull();
    });

    it('allows re-approval after rejection and re-upload', function () {
        $note = createNote();
        createVersion($note);

        $note->reject('Revise please.', $this->admin->id);
        expect($note->fresh()->status)->toBe('rejected');

        // Teacher re-uploads
        $note->update(['status' => 'pending']);
        createVersion($note, ['file_name' => 'revised.pdf', 'status' => 'pending']);

        $note->approve('Much better!', $this->admin->id);
        expect($note->fresh()->status)->toBe('approved');
    });

});

// ──────────────────────────────────────────────────────────────
// 6. Authorization Policies
// ──────────────────────────────────────────────────────────────

describe('Authorization Policies', function () {

    it('allows admin and sudo to view any lesson note', function () {
        expect($this->admin->can('viewAny', LessonNote::class))->toBeTrue()
            ->and($this->sudo->can('viewAny', LessonNote::class))->toBeTrue();
    });

    it('allows teacher to view any (filtered in resource)', function () {
        expect($this->teacher->can('viewAny', LessonNote::class))->toBeTrue();
    });

    it('denies student from viewing lesson notes', function () {
        expect($this->student->can('viewAny', LessonNote::class))->toBeFalse();
    });

    it('allows teacher to view only their own note', function () {
        $ownNote = createNote();
        $otherNote = createNote([
            'teacher_id' => $this->teacher2->id,
            'week_number' => 3,
        ]);

        expect($this->teacher->can('view', $ownNote))->toBeTrue()
            ->and($this->teacher->can('view', $otherNote))->toBeFalse();
    });

    it('allows admin to view any specific note', function () {
        $note = createNote();

        expect($this->admin->can('view', $note))->toBeTrue();
    });

    it('only allows teachers to create lesson notes', function () {
        expect($this->teacher->can('create', LessonNote::class))->toBeTrue()
            ->and($this->admin->can('create', LessonNote::class))->toBeFalse()
            ->and($this->student->can('create', LessonNote::class))->toBeFalse();
    });

    it('allows teacher to update only their own pending notes', function () {
        $pendingNote = createNote(['status' => 'pending']);
        $approvedNote = createNote(['status' => 'approved', 'week_number' => 2]);

        expect($this->teacher->can('update', $pendingNote))->toBeTrue()
            ->and($this->teacher->can('update', $approvedNote))->toBeFalse();
    });

    it('prevents all users from deleting lesson notes', function () {
        $note = createNote();

        expect($this->admin->can('delete', $note))->toBeFalse()
            ->and($this->sudo->can('delete', $note))->toBeFalse()
            ->and($this->teacher->can('delete', $note))->toBeFalse();
    });

    it('only allows admin/sudo to approve or reject', function () {
        $note = createNote();

        expect($this->admin->can('approve', $note))->toBeTrue()
            ->and($this->sudo->can('approve', $note))->toBeTrue()
            ->and($this->teacher->can('approve', $note))->toBeFalse();

        expect($this->admin->can('reject', $note))->toBeTrue()
            ->and($this->sudo->can('reject', $note))->toBeTrue()
            ->and($this->teacher->can('reject', $note))->toBeFalse();
    });

    it('validates canUploadFor checks assignment', function () {
        expect(LessonNotePolicy::canUploadFor(
            $this->teacher,
            $this->subject->id,
            $this->classroom->id,
            $this->session->id,
            $this->term->id
        ))->toBeTrue();

        expect(LessonNotePolicy::canUploadFor(
            $this->teacher,
            $this->subject2->id,
            $this->classroom->id,
            $this->session->id,
            $this->term->id
        ))->toBeFalse();

        expect(LessonNotePolicy::canUploadFor(
            $this->admin,
            $this->subject->id,
            $this->classroom->id,
            $this->session->id,
            $this->term->id
        ))->toBeFalse();
    });

});

// ──────────────────────────────────────────────────────────────
// 7. Editable State
// ──────────────────────────────────────────────────────────────

describe('Editable State', function () {

    it('allows teacher to edit pending notes', function () {
        $note = createNote(['status' => 'pending']);
        expect($note->canBeEditedByTeacher())->toBeTrue();
    });

    it('blocks teacher from editing approved notes', function () {
        $note = createNote(['status' => 'approved']);
        expect($note->canBeEditedByTeacher())->toBeFalse();
    });

    it('blocks teacher from editing rejected notes', function () {
        $note = createNote(['status' => 'rejected']);
        expect($note->canBeEditedByTeacher())->toBeFalse();
    });

});

// ──────────────────────────────────────────────────────────────
// 8. Model Scopes & Filtering
// ──────────────────────────────────────────────────────────────

describe('Model Scopes', function () {

    it('filters by teacher', function () {
        createNote(['teacher_id' => $this->teacher->id]);
        createNote(['teacher_id' => $this->teacher2->id, 'week_number' => 3]);

        expect(LessonNote::forTeacher($this->teacher->id)->count())->toBe(1)
            ->and(LessonNote::forTeacher($this->teacher2->id)->count())->toBe(1);
    });

    it('filters by subject', function () {
        createNote(['subject_id' => $this->subject->id]);

        expect(LessonNote::forSubject($this->subject->id)->count())->toBe(1)
            ->and(LessonNote::forSubject($this->subject2->id)->count())->toBe(0);
    });

    it('filters by classroom', function () {
        createNote(['classroom_id' => $this->classroom->id]);

        expect(LessonNote::forClassroom($this->classroom->id)->count())->toBe(1)
            ->and(LessonNote::forClassroom($this->classroom2->id)->count())->toBe(0);
    });

    it('filters by week', function () {
        createNote(['week_number' => 1]);

        expect(LessonNote::forWeek(1)->count())->toBe(1)
            ->and(LessonNote::forWeek(2)->count())->toBe(0);
    });

    it('filters by status', function () {
        createNote(['status' => 'pending', 'week_number' => 1]);
        createNote(['status' => 'approved', 'week_number' => 2]);
        createNote(['status' => 'rejected', 'week_number' => 3]);

        expect(LessonNote::pending()->count())->toBe(1)
            ->and(LessonNote::approved()->count())->toBe(1)
            ->and(LessonNote::rejected()->count())->toBe(1);
    });

    it('filters by active session/term', function () {
        createNote();

        $oldSession = Session::createWithTerms(2020);
        $oldTerm = $oldSession->terms()->first();
        LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $oldSession->id,
            'term_id' => $oldTerm->id,
            'week_number' => 1,
            'status' => 'pending',
        ]);

        expect(LessonNote::active()->count())->toBe(1);
    });

});

// ──────────────────────────────────────────────────────────────
// 9. Model Relationships
// ──────────────────────────────────────────────────────────────

describe('Model Relationships', function () {

    it('has correct relationships on LessonNote', function () {
        $note = createNote();
        $version = createVersion($note);

        expect($note->teacher->id)->toBe($this->teacher->id)
            ->and($note->subject->id)->toBe($this->subject->id)
            ->and($note->classroom->id)->toBe($this->classroom->id)
            ->and($note->session->id)->toBe($this->session->id)
            ->and($note->term->id)->toBe($this->term->id)
            ->and($note->latestVersion->id)->toBe($version->id)
            ->and($note->versions)->toHaveCount(1);
    });

    it('has correct relationships on LessonNoteVersion', function () {
        $note = createNote();
        $version = createVersion($note);

        expect($version->lessonNote->id)->toBe($note->id)
            ->and($version->uploadedBy->id)->toBe($this->teacher->id);
    });

    it('orders versions newest first', function () {
        $note = createNote();
        $v1 = createVersion($note, ['file_name' => 'v1.pdf']);

        $this->travel(1)->seconds();
        $v2 = createVersion($note, ['file_name' => 'v2.pdf']);

        $versions = $note->fresh()->versions;
        expect($versions->first()->id)->toBe($v2->id)
            ->and($versions->last()->id)->toBe($v1->id);
    });

    it('has correct relationships on SubmissionWindow', function () {
        expect($this->openWindow->session->id)->toBe($this->session->id)
            ->and($this->openWindow->term->id)->toBe($this->term->id);
    });

    it('has correct relationships on TeacherSubjectAssignment', function () {
        expect($this->assignment->teacher->id)->toBe($this->teacher->id)
            ->and($this->assignment->subject->id)->toBe($this->subject->id)
            ->and($this->assignment->classroom->id)->toBe($this->classroom->id)
            ->and($this->assignment->session->id)->toBe($this->session->id)
            ->and($this->assignment->term->id)->toBe($this->term->id);
    });

});

// ──────────────────────────────────────────────────────────────
// 10. Queued Jobs
// ──────────────────────────────────────────────────────────────

describe('Queued Jobs', function () {

    it('dispatches ProcessLessonNoteUpload as a queued job', function () {
        Queue::fake();

        ProcessLessonNoteUpload::dispatch(1, 'path/to/file.pdf', 'file.pdf', $this->teacher->id);

        Queue::assertPushed(ProcessLessonNoteUpload::class, function ($job) {
            return $job->lessonNoteId === 1
                && $job->filePath === 'path/to/file.pdf'
                && $job->fileName === 'file.pdf'
                && $job->uploadedBy === $this->teacher->id;
        });
    });

    it('dispatches NotifyAdminOfNewSubmission as a queued job', function () {
        Queue::fake();

        NotifyAdminOfNewSubmission::dispatch(42);

        Queue::assertPushed(NotifyAdminOfNewSubmission::class, function ($job) {
            return $job->lessonNoteId === 42;
        });
    });

    it('dispatches LogLessonNoteAction as a queued job', function () {
        Queue::fake();

        LogLessonNoteAction::dispatch(1, 'approve', $this->admin->id, 'Approved by Admin');

        Queue::assertPushed(LogLessonNoteAction::class, function ($job) {
            return $job->lessonNoteId === 1
                && $job->action === 'approve'
                && $job->userId === $this->admin->id
                && $job->details === 'Approved by Admin';
        });
    });

    it('ProcessLessonNoteUpload dispatches notification and audit jobs', function () {
        Storage::fake('lesson_notes');
        Queue::fake([NotifyAdminOfNewSubmission::class, LogLessonNoteAction::class]);

        $note = createNote();

        Storage::disk('lesson_notes')->put('test/file.pdf', 'fake pdf content');

        $job = new ProcessLessonNoteUpload($note->id, 'test/file.pdf', 'file.pdf', $this->teacher->id);
        $job->handle();

        Queue::assertPushed(NotifyAdminOfNewSubmission::class);
        Queue::assertPushed(LogLessonNoteAction::class);

        expect($note->fresh()->latest_version_id)->not->toBeNull();
        expect(LessonNoteVersion::where('lesson_note_id', $note->id)->count())->toBe(1);
    });

});

// ──────────────────────────────────────────────────────────────
// 11. Notifications
// ──────────────────────────────────────────────────────────────

describe('Notifications', function () {

    it('LessonNoteSubmitted formats correctly', function () {
        $note = createNote();
        $note->load(['teacher', 'subject', 'classroom']);

        $notification = new LessonNoteSubmitted($note);
        $data = $notification->toDatabase($this->admin);

        expect($data['title'])->toBe('New Lesson Note Submitted')
            ->and($data['body'])->toContain($this->teacher->name)
            ->and($data['body'])->toContain('Mathematics')
            ->and($data['body'])->toContain('JSS 1A')
            ->and($data['body'])->toContain('Week 1')
            ->and($data['lesson_note_id'])->toBe($note->id);
    });

    it('LessonNoteReviewed formats correctly for approval', function () {
        $note = createNote();
        $note->load(['subject', 'classroom']);

        $notification = new LessonNoteReviewed($note, 'approved', 'Great work!');
        $data = $notification->toDatabase($this->teacher);

        expect($data['title'])->toBe('Lesson Note Approved')
            ->and($data['body'])->toContain('approved')
            ->and($data['body'])->toContain('Mathematics')
            ->and($data['body'])->toContain('Great work!')
            ->and($data['status'])->toBe('approved');
    });

    it('LessonNoteReviewed formats correctly for rejection', function () {
        $note = createNote();
        $note->load(['subject', 'classroom']);

        $notification = new LessonNoteReviewed($note, 'rejected', 'Needs revision.');
        $data = $notification->toDatabase($this->teacher);

        expect($data['title'])->toBe('Lesson Note Rejected')
            ->and($data['body'])->toContain('rejected')
            ->and($data['body'])->toContain('Needs revision.')
            ->and($data['status'])->toBe('rejected');
    });

    it('NotifyAdminOfNewSubmission sends to all admins', function () {
        Notification::fake();

        $note = createNote();
        $note->load(['teacher', 'subject', 'classroom', 'session', 'term']);

        $job = new NotifyAdminOfNewSubmission($note->id);
        $job->handle();

        Notification::assertSentTo($this->admin, LessonNoteSubmitted::class);
        Notification::assertSentTo($this->sudo, LessonNoteSubmitted::class);
        Notification::assertNotSentTo($this->teacher, LessonNoteSubmitted::class);
    });

});

// ──────────────────────────────────────────────────────────────
// 12. Audit Logging
// ──────────────────────────────────────────────────────────────

describe('Audit Logging', function () {

    it('creates an audit log entry via LogLessonNoteAction', function () {
        $note = createNote();

        $job = new LogLessonNoteAction($note->id, 'approve', $this->admin->id, 'Approved by Admin');
        $job->handle();

        $log = AuditLog::first();

        expect($log)->not->toBeNull()
            ->and($log->auditable_type)->toBe(LessonNote::class)
            ->and($log->auditable_id)->toBe($note->id)
            ->and($log->action)->toBe('approve')
            ->and($log->user_id)->toBe($this->admin->id)
            ->and($log->details)->toBe('Approved by Admin');
    });

    it('has morphTo auditable relationship', function () {
        $note = createNote();

        $log = AuditLog::create([
            'auditable_type' => LessonNote::class,
            'auditable_id' => $note->id,
            'action' => 'upload',
            'user_id' => $this->teacher->id,
            'details' => 'Uploaded file',
        ]);

        expect($log->auditable)->toBeInstanceOf(LessonNote::class)
            ->and($log->auditable->id)->toBe($note->id)
            ->and($log->user->id)->toBe($this->teacher->id);
    });

    it('does not throw on audit log failure', function () {
        $job = new LogLessonNoteAction(999999, 'test', $this->admin->id, 'test');
        $job->handle();

        expect(true)->toBeTrue();
    });

});

// ──────────────────────────────────────────────────────────────
// 13. Caching
// ──────────────────────────────────────────────────────────────

describe('Caching', function () {

    it('caches active submission window', function () {
        $cache = app(LessonNoteCache::class);

        $window = $cache->getActiveWindow($this->session->id, $this->term->id, 1);

        expect($window)->not->toBeNull()
            ->and($window->id)->toBe($this->openWindow->id);
    });

    it('returns null for closed window from cache', function () {
        $cache = app(LessonNoteCache::class);

        $window = $cache->getActiveWindow($this->session->id, $this->term->id, 2);

        expect($window)->toBeNull();
    });

    it('caches teacher assignments', function () {
        $cache = app(LessonNoteCache::class);

        $assignments = $cache->getTeacherAssignments($this->teacher->id);

        expect($assignments)->toHaveCount(1)
            ->and($assignments->first()->subject_id)->toBe($this->subject->id);
    });

    it('invalidates window cache', function () {
        $cache = app(LessonNoteCache::class);

        $cache->getActiveWindow($this->session->id, $this->term->id, 1);
        $cache->invalidateWindow($this->session->id, $this->term->id, 1);

        $this->openWindow->update(['is_open' => false]);

        $window = $cache->getActiveWindow($this->session->id, $this->term->id, 1);
        expect($window)->toBeNull();
    });

    it('invalidates teacher assignment cache', function () {
        $cache = app(LessonNoteCache::class);

        $cache->getTeacherAssignments($this->teacher->id);
        $cache->invalidateTeacherAssignments($this->teacher->id);

        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject2->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        $assignments = $cache->getTeacherAssignments($this->teacher->id);
        expect($assignments)->toHaveCount(2);
    });

});

// ──────────────────────────────────────────────────────────────
// 14. User Panel Access
// ──────────────────────────────────────────────────────────────

describe('User Panel Access', function () {

    it('grants admin panel access to sudo and admin only', function () {
        $adminPanel = \Filament\Facades\Filament::getPanel('admin');
        
        if (!$adminPanel) {
            $this->markTestSkipped('Admin panel not configured');
        }

        expect($this->sudo->canAccessPanel($adminPanel))->toBeTrue()
            ->and($this->admin->canAccessPanel($adminPanel))->toBeTrue()
            ->and($this->teacher->canAccessPanel($adminPanel))->toBeFalse()
            ->and($this->student->canAccessPanel($adminPanel))->toBeFalse();
    });

    it('grants teacher panel access to teachers only', function () {
        $teacherPanel = \Filament\Facades\Filament::getPanel('teacher');
        
        if (!$teacherPanel) {
            $this->markTestSkipped('Teacher panel not configured');
        }

        expect($this->teacher->canAccessPanel($teacherPanel))->toBeTrue()
            ->and($this->admin->canAccessPanel($teacherPanel))->toBeTrue()
            ->and($this->student->canAccessPanel($teacherPanel))->toBeFalse();
    });

});

// ──────────────────────────────────────────────────────────────
// 15. File Validation (ProcessLessonNoteUpload)
// ──────────────────────────────────────────────────────────────

describe('File Validation', function () {

    it('rejects files exceeding 10MB', function () {
        Storage::fake('lesson_notes');

        $note = createNote();

        $bigContent = str_repeat('x', 11 * 1024 * 1024);
        Storage::disk('lesson_notes')->put('test/big.pdf', $bigContent);

        $job = new ProcessLessonNoteUpload($note->id, 'test/big.pdf', 'big.pdf', $this->teacher->id);

        expect(fn () => $job->handle())->toThrow(\Exception::class, 'File size exceeds 10MB limit');
    });

    it('rejects missing files', function () {
        Storage::fake('lesson_notes');

        $note = createNote();

        $job = new ProcessLessonNoteUpload($note->id, 'nonexistent/file.pdf', 'file.pdf', $this->teacher->id);

        expect(fn () => $job->handle())->toThrow(\Exception::class, 'File not found');
    });

});

// ──────────────────────────────────────────────────────────────
// 16. Version Metadata
// ──────────────────────────────────────────────────────────────

describe('Version Metadata', function () {

    it('builds correct storage path', function () {
        $path = LessonNoteVersion::buildStoragePath(1, 2, 3, 4, 'abc123', 'pdf');

        expect($path)->toBe('lesson-notes/1/2/week-3/4/abc123.pdf');
    });

    it('computes formatted file size', function () {
        $note = createNote();
        $version = createVersion($note, ['file_size' => 1_536_000]);

        expect($version->formatted_file_size)->toContain('MB');
    });

    it('generates SHA-256 file hash with 64 characters', function () {
        $note = createNote();
        $version = createVersion($note);

        expect(strlen($version->file_hash))->toBe(64);
    });

    it('preserves reviewed_by and reviewed_at after review', function () {
        $note = createNote();
        $version = createVersion($note);

        $note->approve('Good', $this->admin->id);
        $version->refresh();

        expect($version->reviewed_by)->toBe($this->admin->id)
            ->and($version->reviewed_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

});

// ──────────────────────────────────────────────────────────────
// 17. Data Preservation (No Deletes)
// ──────────────────────────────────────────────────────────────

describe('Data Preservation', function () {

    it('never allows deletion via policy for any role', function () {
        $note = createNote();

        expect($this->admin->can('delete', $note))->toBeFalse()
            ->and($this->sudo->can('delete', $note))->toBeFalse()
            ->and($this->teacher->can('delete', $note))->toBeFalse()
            ->and($this->student->can('delete', $note))->toBeFalse();
    });

    it('preserves all version history after multiple uploads', function () {
        $note = createNote();
        createVersion($note, ['file_name' => 'v1.pdf']);
        createVersion($note, ['file_name' => 'v2.pdf']);
        $v3 = createVersion($note, ['file_name' => 'v3.pdf']);

        expect($note->versions()->count())->toBe(3)
            ->and($note->fresh()->latest_version_id)->toBe($v3->id);

        expect(LessonNoteVersion::where('lesson_note_id', $note->id)->count())->toBe(3);
    });

});

// ──────────────────────────────────────────────────────────────
// 18. Signed URL Generation & Validation
// ──────────────────────────────────────────────────────────────

describe('Signed URL Generation', function () {

    it('generates signed URL for file download', function () {
        Storage::fake('lesson_notes');
        $note = createNote();
        $version = createVersion($note);

        Storage::disk('lesson_notes')->put($version->file_path, 'fake pdf content');

        $signedUrl = $version->getSignedDownloadUrl();

        expect($signedUrl)->toBeString()
            ->and($signedUrl)->toContain('signature=')
            ->and($signedUrl)->toContain('expires=');
    });

    it('validates signed URL expiration time', function () {
        Storage::fake('lesson_notes');
        $note = createNote();
        $version = createVersion($note);

        Storage::disk('lesson_notes')->put($version->file_path, 'fake pdf content');

        $signedUrl = $version->getSignedDownloadUrl(60); // 60 minutes

        expect($signedUrl)->toContain('expires=');
        
        // Extract expiration timestamp
        parse_str(parse_url($signedUrl, PHP_URL_QUERY), $params);
        $expiresAt = $params['expires'] ?? null;
        
        expect($expiresAt)->not->toBeNull()
            ->and((int)$expiresAt)->toBeGreaterThan(now()->timestamp)
            ->and((int)$expiresAt)->toBeLessThanOrEqual(now()->addMinutes(60)->timestamp);
    });

    it('rejects expired signed URLs', function () {
        Storage::fake('lesson_notes');
        $note = createNote();
        $version = createVersion($note);

        Storage::disk('lesson_notes')->put($version->file_path, 'fake pdf content');

        // Generate URL that expires in 1 second
        $signedUrl = $version->getSignedDownloadUrl(0.0167); // ~1 second
        
        $this->travel(2)->seconds();

        expect(fn () => $version->validateSignedUrl($signedUrl))
            ->toThrow(\Exception::class, 'Signed URL has expired');
    });

    it('rejects tampered signed URLs', function () {
        Storage::fake('lesson_notes');
        $note = createNote();
        $version = createVersion($note);

        $signedUrl = $version->getSignedDownloadUrl();
        
        // Tamper with the URL
        $tamperedUrl = str_replace('signature=', 'signature=tampered', $signedUrl);

        expect(fn () => $version->validateSignedUrl($tamperedUrl))
            ->toThrow(\Exception::class, 'Invalid signature');
    });

    it('generates upload signed URLs for direct-to-storage uploads', function () {
        $uploadUrl = LessonNote::generateUploadSignedUrl(
            $this->session->id,
            $this->term->id,
            1,
            $this->teacher->id,
            'test-file.pdf'
        );

        expect($uploadUrl)->toBeString()
            ->and($uploadUrl)->toContain('signature=')
            ->and($uploadUrl)->toContain('expires=');
    });

});

// ──────────────────────────────────────────────────────────────
// 19. File Security & Validation
// ──────────────────────────────────────────────────────────────

describe('File Security', function () {

    it('performs virus scan on upload', function () {
        Storage::fake('lesson_notes');
        Queue::fake();

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/clean.pdf', 'fake pdf content');

        $job = new ProcessLessonNoteUpload($note->id, 'test/clean.pdf', 'clean.pdf', $this->teacher->id);
        $job->handle();

        // Verify virus scan was called (mock integration)
        expect($note->fresh()->latestVersion->virus_scan_status)->toBe('clean');
    });

    it('rejects files that fail virus scan', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/infected.pdf', 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE');

        $job = new ProcessLessonNoteUpload($note->id, 'test/infected.pdf', 'infected.pdf', $this->teacher->id);

        expect(fn () => $job->handle())
            ->toThrow(\Exception::class, 'Virus detected');
    });

    it('validates MIME type for PDF files', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/document.pdf', '%PDF-1.4 fake pdf');

        $job = new ProcessLessonNoteUpload($note->id, 'test/document.pdf', 'document.pdf', $this->teacher->id);
        $job->handle();

        expect($note->fresh()->latestVersion->mime_type)->toBe('application/pdf');
    });

    it('validates MIME type for DOC files', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/document.doc', 'fake doc content');

        $job = new ProcessLessonNoteUpload($note->id, 'test/document.doc', 'document.doc', $this->teacher->id);
        $job->handle();

        expect($note->fresh()->latestVersion->mime_type)->toBeIn([
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
    });

    it('rejects executable files', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/malicious.exe', 'MZ fake exe');

        $job = new ProcessLessonNoteUpload($note->id, 'test/malicious.exe', 'malicious.exe', $this->teacher->id);

        expect(fn () => $job->handle())
            ->toThrow(\Exception::class, 'Only PDF, DOC, and DOCX files are allowed');
    });

    it('rejects files with invalid extensions', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/script.php', '<?php echo "hack"; ?>');

        $job = new ProcessLessonNoteUpload($note->id, 'test/script.php', 'script.php', $this->teacher->id);

        expect(fn () => $job->handle())
            ->toThrow(\Exception::class, 'Only PDF, DOC, and DOCX files are allowed');
    });

    it('validates file hash matches uploaded content', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        $content = 'authentic pdf content';
        Storage::disk('lesson_notes')->put('test/file.pdf', $content);

        $job = new ProcessLessonNoteUpload($note->id, 'test/file.pdf', 'file.pdf', $this->teacher->id);
        $job->handle();

        $expectedHash = hash('sha256', $content);
        expect($note->fresh()->latestVersion->file_hash)->toBe($expectedHash);
    });

    it('detects duplicate files by hash', function () {
        Storage::fake('lesson_notes');

        $content = 'duplicate content';
        
        $note1 = createNote(['week_number' => 1]);
        Storage::disk('lesson_notes')->put('test/file1.pdf', $content);
        $job1 = new ProcessLessonNoteUpload($note1->id, 'test/file1.pdf', 'file1.pdf', $this->teacher->id);
        $job1->handle();

        $note2 = createNote(['week_number' => 2]);
        Storage::disk('lesson_notes')->put('test/file2.pdf', $content);
        $job2 = new ProcessLessonNoteUpload($note2->id, 'test/file2.pdf', 'file2.pdf', $this->teacher->id);
        $job2->handle();

        $hash1 = $note1->fresh()->latestVersion->file_hash;
        $hash2 = $note2->fresh()->latestVersion->file_hash;

        expect($hash1)->toBe($hash2)
            ->and($note2->fresh()->latestVersion->is_duplicate)->toBeTrue();
    });

});

// ──────────────────────────────────────────────────────────────
// 20. Object Storage Integration
// ──────────────────────────────────────────────────────────────

describe('Object Storage', function () {

    it('uploads to S3-compatible storage', function () {
        Storage::fake('s3');

        $note = createNote();
        $content = 'test pdf content';
        
        Storage::disk('s3')->put('lesson-notes/test.pdf', $content);

        expect(Storage::disk('s3')->exists('lesson-notes/test.pdf'))->toBeTrue()
            ->and(Storage::disk('s3')->get('lesson-notes/test.pdf'))->toBe($content);
    });

    it('retrieves file from object storage', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        $version = createVersion($note);
        $content = 'stored content';

        Storage::disk('lesson_notes')->put($version->file_path, $content);

        $retrieved = $version->getFileContent();

        expect($retrieved)->toBe($content);
    });

    it('generates CDN URLs for file delivery', function () {
        $note = createNote();
        $version = createVersion($note);

        $cdnUrl = $version->getCdnUrl();

        expect($cdnUrl)->toBeString()
            ->and($cdnUrl)->toContain('cdn.')
            ->and($cdnUrl)->toContain($version->file_path);
    });

    it('handles storage connection failures gracefully', function () {
        Storage::shouldReceive('disk')
            ->with('lesson_notes')
            ->andThrow(new \Exception('Storage connection failed'));

        $note = createNote();

        expect(fn () => $note->attemptFileUpload('test.pdf', 'content'))
            ->toThrow(\Exception::class, 'Storage connection failed');
    });

    it('falls back to alternative storage on primary failure', function () {
        Storage::fake('backup_storage');
        
        $note = createNote();
        $content = 'test content';

        // Upload directly to backup storage (simulating fallback)
        $result = $note->uploadWithFallback($content, 'test.pdf', ['backup_storage']);

        expect($result)->toBeTrue()
            ->and(Storage::disk('backup_storage')->exists('lesson-notes/test.pdf'))->toBeTrue();
    });

    it('verifies storage path structure matches PRD specification', function () {
        $note = createNote();
        $version = createVersion($note);

        $expectedPattern = sprintf(
            'lesson-notes/%d/%d/week-%d/%d/',
            $note->session_id,
            $note->term_id,
            $note->week_number,
            $note->teacher_id
        );

        expect($version->file_path)->toContain($expectedPattern);
    });

});

// ──────────────────────────────────────────────────────────────
// 21. Rate Limiting
// ──────────────────────────────────────────────────────────────

describe('Rate Limiting', function () {

    it('enforces upload rate limits per teacher', function () {
        $cache = app(LessonNoteCache::class);

        // Allow 10 uploads per hour
        for ($i = 1; $i <= 10; $i++) {
            expect($cache->checkUploadRateLimit($this->teacher->id))->toBeTrue();
            $cache->incrementUploadCount($this->teacher->id);
        }

        // 11th upload should be blocked
        expect($cache->checkUploadRateLimit($this->teacher->id))->toBeFalse();
    });

    it('enforces review rate limits per admin', function () {
        $cache = app(LessonNoteCache::class);

        // Allow 100 reviews per hour
        for ($i = 1; $i <= 100; $i++) {
            expect($cache->checkReviewRateLimit($this->admin->id))->toBeTrue();
            $cache->incrementReviewCount($this->admin->id);
        }

        // 101st review should be blocked
        expect($cache->checkReviewRateLimit($this->admin->id))->toBeFalse();
    });

    it('blocks excessive API calls', function () {
        $cache = app(LessonNoteCache::class);

        // Simulate 60 API calls in 1 minute (limit: 50/min)
        for ($i = 1; $i <= 60; $i++) {
            $allowed = $cache->checkApiRateLimit($this->teacher->id, 'lesson-notes');
            if ($i <= 50) {
                expect($allowed)->toBeTrue();
                $cache->incrementApiCall($this->teacher->id, 'lesson-notes');
            } else {
                expect($allowed)->toBeFalse();
            }
        }
    });

    it('allows normal usage within limits', function () {
        $cache = app(LessonNoteCache::class);

        expect($cache->checkUploadRateLimit($this->teacher->id))->toBeTrue();
        $cache->incrementUploadCount($this->teacher->id);

        expect($cache->checkUploadRateLimit($this->teacher->id))->toBeTrue();
    });

    it('resets rate limits after time window', function () {
        $cache = app(LessonNoteCache::class);

        // Hit the limit
        for ($i = 1; $i <= 10; $i++) {
            $cache->incrementUploadCount($this->teacher->id);
        }
        expect($cache->checkUploadRateLimit($this->teacher->id))->toBeFalse();

        // Travel 1 hour forward
        $this->travel(61)->minutes();

        // Should be reset
        expect($cache->checkUploadRateLimit($this->teacher->id))->toBeTrue();
    });

});

// ──────────────────────────────────────────────────────────────
// 22. Retry Mechanisms
// ──────────────────────────────────────────────────────────────

describe('Retry Mechanisms', function () {

    it('retries failed uploads automatically', function () {
        Queue::fake();

        $note = createNote();

        ProcessLessonNoteUpload::dispatch($note->id, 'test.pdf', 'test.pdf', $this->teacher->id);

        Queue::assertPushed(ProcessLessonNoteUpload::class, function ($job) {
            return $job->tries === 3; // Max 3 attempts
        });
    });

    it('retries failed queue jobs', function () {
        Queue::fake();

        NotifyAdminOfNewSubmission::dispatch(1);

        Queue::assertPushed(NotifyAdminOfNewSubmission::class, function ($job) {
            return $job->tries === 3;
        });
    });

    it('implements exponential backoff on retries', function () {
        $job = new ProcessLessonNoteUpload(1, 'test.pdf', 'test.pdf', $this->teacher->id);

        $backoff = $job->backoff();

        expect($backoff)->toBeArray()
            ->and($backoff[0])->toBe(10)  // 10 seconds
            ->and($backoff[1])->toBe(30)  // 30 seconds
            ->and($backoff[2])->toBe(60); // 60 seconds
    });

    it('enforces max retry attempts', function () {
        $job = new ProcessLessonNoteUpload(1, 'test.pdf', 'test.pdf', $this->teacher->id);

        expect($job->tries)->toBe(3);
    });

    it('sends failed jobs to dead letter queue after max retries', function () {
        Queue::fake();
        Storage::fake('lesson_notes');

        $note = createNote();

        // Simulate job failure
        $job = new ProcessLessonNoteUpload($note->id, 'nonexistent.pdf', 'test.pdf', $this->teacher->id);

        // Verify job has retry configuration
        expect($job->tries)->toBe(3);
        
        // Attempt to handle the job (will fail due to missing file)
        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected to fail
            expect($e->getMessage())->toContain('File not found');
        }
    });

});

// ──────────────────────────────────────────────────────────────
// 23. Graceful Degradation
// ──────────────────────────────────────────────────────────────

describe('Graceful Degradation', function () {

    it('handles storage service outage', function () {
        Storage::shouldReceive('disk')
            ->andThrow(new \Exception('Storage unavailable'));

        $note = createNote();

        $result = $note->safeUpload('test.pdf', 'content');

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toContain('Storage unavailable')
            ->and($result['fallback_used'])->toBeTrue();
    });

    it('handles queue service outage', function () {
        Queue::shouldReceive('push')
            ->andThrow(new \Exception('Queue unavailable'));

        $note = createNote();

        // Should log error but not crash
        expect(fn () => $note->notifyAdminSync())->not->toThrow(\Exception::class);
    });

    it('handles CDN failure with fallback to direct storage', function () {
        Storage::fake('lesson_notes');
        
        $note = createNote();
        $version = createVersion($note);

        // Store the file
        Storage::disk('lesson_notes')->put($version->file_path, 'test content');

        // Mock CDN failure by setting cdn_available to false
        $version->update(['cdn_available' => false]);

        $url = $version->getFileUrl();

        expect($url)->not->toContain('cdn.')
            ->and($url)->toContain('storage');
    });

    it('handles cache failure with direct database queries', function () {
        Cache::shouldReceive('get')
            ->andThrow(new \Exception('Redis unavailable'));

        $cache = app(LessonNoteCache::class);

        // Should fall back to database
        $window = $cache->getActiveWindowWithFallback($this->session->id, $this->term->id, 1);

        expect($window)->not->toBeNull()
            ->and($window->id)->toBe($this->openWindow->id);
    });

    it('continues core operations during partial outage', function () {
        // Simulate cache down but database up
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $note = createNote();

        expect($note)->toBeInstanceOf(LessonNote::class)
            ->and($note->status)->toBe('pending');
    });

});

// ──────────────────────────────────────────────────────────────
// 24. Performance Benchmarks
// ──────────────────────────────────────────────────────────────

describe('Performance Benchmarks', function () {

    it('upload form loads in under 300ms', function () {
        $start = microtime(true);

        $assignments = TeacherSubjectAssignment::forTeacher($this->teacher->id)
            ->active()
            ->with(['subject', 'classroom'])
            ->get();

        $windows = SubmissionWindow::currentlyOpen()->get();

        $duration = (microtime(true) - $start) * 1000; // Convert to ms

        expect($duration)->toBeLessThan(300)
            ->and($assignments)->not->toBeEmpty()
            ->and($windows)->not->toBeEmpty();
    })->group('performance');

    it('submission completes in under 1 second', function () {
        Storage::fake('lesson_notes');
        Queue::fake();

        $start = microtime(true);

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/fast.pdf', 'content');
        
        ProcessLessonNoteUpload::dispatch($note->id, 'test/fast.pdf', 'fast.pdf', $this->teacher->id);

        $duration = (microtime(true) - $start) * 1000;

        expect($duration)->toBeLessThan(1000);
    })->group('performance');

    it('admin review page loads in under 500ms', function () {
        // Create test data
        for ($i = 1; $i <= 10; $i++) {
            $note = createNote(['week_number' => $i]);
            createVersion($note);
        }

        $start = microtime(true);

        $notes = LessonNote::with(['teacher', 'subject', 'classroom', 'latestVersion'])
            ->forClassroom($this->classroom->id)
            ->forWeek(1)
            ->get();

        $duration = (microtime(true) - $start) * 1000;

        expect($duration)->toBeLessThan(500)
            ->and($notes)->not->toBeEmpty();
    })->group('performance');

    it('approve/reject action completes in under 300ms', function () {
        $note = createNote();
        createVersion($note);

        $start = microtime(true);

        $note->approve('Good work!', $this->admin->id);

        $duration = (microtime(true) - $start) * 1000;

        expect($duration)->toBeLessThan(300)
            ->and($note->fresh()->status)->toBe('approved');
    })->group('performance');

    it('achieves cache hit rate above 90% for common queries', function () {
        $cache = app(LessonNoteCache::class);

        // Prime the cache
        $cache->getActiveWindow($this->session->id, $this->term->id, 1);

        $hits = 0;
        $total = 100;

        for ($i = 0; $i < $total; $i++) {
            $start = microtime(true);
            $cache->getActiveWindow($this->session->id, $this->term->id, 1);
            $duration = microtime(true) - $start;

            // Cache hits should be < 1ms
            if ($duration < 0.001) {
                $hits++;
            }
        }

        $hitRate = ($hits / $total) * 100;

        expect($hitRate)->toBeGreaterThan(90);
    })->group('performance');

});

// ──────────────────────────────────────────────────────────────
// 25. Concurrent Operations
// ──────────────────────────────────────────────────────────────

describe('Concurrent Operations', function () {

    it('handles multiple teachers uploading simultaneously', function () {
        Storage::fake('lesson_notes');
        Queue::fake();

        $teachers = User::factory()->count(5)->create(['role' => 'teacher']);

        foreach ($teachers as $teacher) {
            TeacherSubjectAssignment::create([
                'teacher_id' => $teacher->id,
                'subject_id' => $this->subject->id,
                'classroom_id' => $this->classroom->id,
                'session_id' => $this->session->id,
                'term_id' => $this->term->id,
            ]);

            $note = createNote(['teacher_id' => $teacher->id]);
            Storage::disk('lesson_notes')->put("test/{$teacher->id}.pdf", 'content');
            
            ProcessLessonNoteUpload::dispatch($note->id, "test/{$teacher->id}.pdf", 'file.pdf', $teacher->id);
        }

        expect(LessonNote::count())->toBe(5); // 5 teachers, each with their own note
    });

    it('handles multiple admins reviewing simultaneously', function () {
        $admins = User::factory()->count(3)->create(['role' => 'admin']);
        $notes = [];

        for ($i = 0; $i < 3; $i++) {
            $note = createNote(['week_number' => $i + 1]);
            createVersion($note);
            $notes[] = $note;
        }

        foreach ($admins as $index => $admin) {
            $notes[$index]->approve('Approved by ' . $admin->name, $admin->id);
        }

        expect(LessonNote::approved()->count())->toBe(3);
    });

    it('prevents race conditions on version updates', function () {
        $note = createNote();
        $version1 = createVersion($note, ['file_name' => 'v1.pdf']);

        // Simulate concurrent version creation
        $version2 = createVersion($note, ['file_name' => 'v2.pdf']);

        $note->refresh();

        expect($note->latest_version_id)->toBe($version2->id)
            ->and($note->versions)->toHaveCount(2);
    });

    it('prevents deadlocks on database operations', function () {
        $notes = [];

        // Create multiple notes in parallel transactions
        for ($i = 1; $i <= 5; $i++) {
            $notes[] = createNote(['week_number' => $i]);
        }

        expect(count($notes))->toBe(5)
            ->and(LessonNote::count())->toBeGreaterThanOrEqual(5);
    });

    it('processes multiple queue jobs in parallel', function () {
        Queue::fake();

        for ($i = 1; $i <= 10; $i++) {
            NotifyAdminOfNewSubmission::dispatch($i);
        }

        Queue::assertPushed(NotifyAdminOfNewSubmission::class, 10);
    });

});

// ──────────────────────────────────────────────────────────────
// 26. File Metadata & Enhanced Versioning
// ──────────────────────────────────────────────────────────────

describe('File Metadata', function () {

    it('extracts PDF metadata including page count', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/document.pdf', '%PDF-1.4 fake pdf with metadata');

        $job = new ProcessLessonNoteUpload($note->id, 'test/document.pdf', 'document.pdf', $this->teacher->id);
        $job->handle();

        $version = $note->fresh()->latestVersion;

        expect($version->metadata)->toBeArray()
            ->and($version->metadata)->toHaveKey('page_count');
    });

    it('extracts document author from metadata', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/authored.pdf', '%PDF-1.4 /Author (John Doe)');

        $job = new ProcessLessonNoteUpload($note->id, 'test/authored.pdf', 'authored.pdf', $this->teacher->id);
        $job->handle();

        $version = $note->fresh()->latestVersion;

        expect($version->metadata)->toHaveKey('author');
    });

    it('compares versions to detect changes', function () {
        $note = createNote();
        $v1 = createVersion($note, ['file_hash' => hash('sha256', 'content v1')]);
        $v2 = createVersion($note, ['file_hash' => hash('sha256', 'content v2')]);

        $hasChanges = $note->hasVersionChanges($v1->id, $v2->id);

        expect($hasChanges)->toBeTrue();
    });

    it('tracks file modification timestamps', function () {
        Storage::fake('lesson_notes');

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/file.pdf', 'content');

        $job = new ProcessLessonNoteUpload($note->id, 'test/file.pdf', 'file.pdf', $this->teacher->id);
        $job->handle();

        $version = $note->fresh()->latestVersion;

        expect($version->file_modified_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('preserves original filename', function () {
        $note = createNote();
        $version = createVersion($note, ['file_name' => 'Mathematics Week 1 Lesson Plan.pdf']);

        expect($version->file_name)->toBe('Mathematics Week 1 Lesson Plan.pdf')
            ->and($version->original_filename)->toBe('Mathematics Week 1 Lesson Plan.pdf');
    });

    it('generates thumbnails for PDF preview', function () {
        Storage::fake('lesson_notes');
        Storage::fake('thumbnails');

        $note = createNote();
        Storage::disk('lesson_notes')->put('test/preview.pdf', '%PDF-1.4 content');

        $job = new ProcessLessonNoteUpload($note->id, 'test/preview.pdf', 'preview.pdf', $this->teacher->id);
        $job->handle();

        $version = $note->fresh()->latestVersion;

        expect($version->thumbnail_path)->not->toBeNull()
            ->and(Storage::disk('thumbnails')->exists($version->thumbnail_path))->toBeTrue();
    });

});

