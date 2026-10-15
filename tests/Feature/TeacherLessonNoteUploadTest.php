<?php

namespace Tests\Feature;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages\CreateTeacherLessonNote;
use App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages\EditTeacherLessonNote;
use App\Filament\Resources\LessonNoteResource as AdminLessonNoteResource;
use App\Filament\Student\Resources\LessonNoteResource as StudentLessonNoteResource;
use App\Models\Classroom;
use App\Models\LessonNote;
use App\Models\LessonNoteVersion;
use App\Models\LessonPlan;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubmissionWindow;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\LessonSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherLessonNoteUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $admin;
    protected User $studentUser;
    protected Student $student;
    protected Subject $subject;
    protected Classroom $classroom;
    protected Session $session;
    protected Term $term;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('lesson_notes');
        Storage::fake('local');
        Storage::fake('public');

        $this->session = Session::factory()->create(['is_active' => true]);
        $this->term = Term::factory()->create(['session_id' => $this->session->id, 'is_active' => true]);

        $this->teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->studentUser = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $this->subject = Subject::factory()->create(['name' => 'Physics']);
        $this->classroom = Classroom::factory()->create(['name' => 'SS2 Diamond', 'class_order' => 11]);

        // Enroll student
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'full_name' => 'Student Diamond',
            'status' => 'active',
            'is_portal_active' => true,
        ]);
        StudentEnrollment::create([
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
        ]);
        $this->classroom->subjects()->attach($this->subject->id);

        // Assign teacher
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        SubmissionWindow::create([
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 2,
        ]);

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('teacher'));
    }

    public function test_teacher_can_create_lesson_note_by_uploading_file()
    {
        $this->actingAs($this->teacher);

        $fakePdf = UploadedFile::fake()->create('physics_wave_motion.pdf', 2048, 'application/pdf');

        Livewire::test(CreateTeacherLessonNote::class)
            ->set('data.subject_id', $this->subject->id)
            ->set('data.classroom_id', $this->classroom->id)
            ->set('data.week_number', 2)
            ->set('data.submission_type', 'file')
            ->set('data.template_file', $fakePdf)
            ->set('data.title', 'Wave Motion and Sound Waves')
            ->call('create')
            ->assertHasNoFormErrors();

        $lessonNote = LessonNote::where('teacher_id', $this->teacher->id)
            ->where('subject_id', $this->subject->id)
            ->where('classroom_id', $this->classroom->id)
            ->where('week_number', 2)
            ->first();

        $this->assertNotNull($lessonNote);
        $this->assertEquals('draft', $lessonNote->status);

        $version = $lessonNote->latestVersion;
        $this->assertNotNull($version);
        $this->assertTrue($version->isFile());
        $this->assertFalse($version->isWritten());
        $this->assertTrue($version->hasValidContent());
        $this->assertEquals('file', $version->submission_type);
        $this->assertEquals('WAVE MOTION AND SOUND WAVES', $version->title);
        $this->assertEquals('physics_wave_motion.pdf', $version->original_filename);
        $this->assertNotNull($version->file_path);

        // Assert file exists on lesson_notes disk
        $this->assertTrue(Storage::disk('lesson_notes')->exists($version->file_path));
    }

    public function test_teacher_can_submit_file_lesson_note_paired_with_lesson_plan_for_review()
    {
        $this->actingAs($this->teacher);

        $fakeDocx = UploadedFile::fake()->create('biology_notes.docx', 1024, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        Livewire::test(CreateTeacherLessonNote::class)
            ->set('data.subject_id', $this->subject->id)
            ->set('data.classroom_id', $this->classroom->id)
            ->set('data.week_number', 2)
            ->set('data.submission_type', 'file')
            ->set('data.template_file', $fakeDocx)
            ->call('create')
            ->assertHasNoFormErrors();

        $lessonNote = LessonNote::where('teacher_id', $this->teacher->id)
            ->where('week_number', 2)
            ->first();

        // Create paired LessonPlan
        $lessonPlan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 2,
            'status' => 'draft',
            'title' => 'Biology Plan Week 2',
        ]);

        $submissionService = app(LessonSubmissionService::class);
        $result = $submissionService->submitPairForReview($lessonNote);

        $this->assertTrue($result['success']);
        $this->assertEquals('pending', $lessonNote->fresh()->status);
        $this->assertEquals('pending', $lessonPlan->fresh()->status);
        $this->assertEquals('pending', $lessonNote->latestVersion->fresh()->status);
    }

    public function test_admin_can_query_and_download_uploaded_lesson_note_file()
    {
        $this->actingAs($this->teacher);

        $fakeDocx = UploadedFile::fake()->create('chemistry_week2.docx', 1024, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        Livewire::test(CreateTeacherLessonNote::class)
            ->set('data.subject_id', $this->subject->id)
            ->set('data.classroom_id', $this->classroom->id)
            ->set('data.week_number', 2)
            ->set('data.submission_type', 'file')
            ->set('data.template_file', $fakeDocx)
            ->call('create')
            ->assertHasNoFormErrors();

        $lessonNote = LessonNote::where('teacher_id', $this->teacher->id)
            ->where('week_number', 2)
            ->first();

        $lessonPlan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 2,
            'status' => 'pending',
            'title' => 'Chemistry Plan',
        ]);

        $lessonNote->update(['status' => 'pending']);
        $lessonNote->latestVersion->update(['status' => 'pending']);

        // Authenticate as Admin
        $this->actingAs($this->admin);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        // Query admin review queue
        $records = AdminLessonNoteResource::getEloquentQuery()->get();
        $this->assertTrue($records->contains('id', $lessonNote->id));

        // Admin download via tenant.lesson-note.file route
        $response = $this->get(route('tenant.lesson-note.file', [
            'version' => $lessonNote->latestVersion->id,
            'download' => 1,
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_student_can_download_approved_uploaded_lesson_note_file()
    {
        $this->actingAs($this->teacher);

        $fakePdf = UploadedFile::fake()->create('physics_notes.pdf', 1024, 'application/pdf');

        Livewire::test(CreateTeacherLessonNote::class)
            ->set('data.subject_id', $this->subject->id)
            ->set('data.classroom_id', $this->classroom->id)
            ->set('data.week_number', 2)
            ->set('data.submission_type', 'file')
            ->set('data.template_file', $fakePdf)
            ->call('create')
            ->assertHasNoFormErrors();

        $lessonNote = LessonNote::where('teacher_id', $this->teacher->id)
            ->where('week_number', 2)
            ->first();

        // 1. Pending note is hidden from student
        $lessonNote->update(['status' => 'pending']);
        $lessonNote->latestVersion->update(['status' => 'pending']);

        $this->actingAs($this->studentUser);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('student'));

        $results = StudentLessonNoteResource::getEloquentQuery()->get();
        $this->assertFalse($results->contains('id', $lessonNote->id));

        // Unauthorized file download while pending
        $unauthResponse = $this->get(route('tenant.lesson-note.file', [
            'version' => $lessonNote->latestVersion->id,
            'download' => 1,
        ]));
        $unauthResponse->assertStatus(403);

        // 2. Approve note
        $lessonNote->update(['status' => 'approved']);
        $lessonNote->latestVersion->update(['status' => 'approved']);

        // Now student sees note
        $approvedResults = StudentLessonNoteResource::getEloquentQuery()->get();
        $this->assertTrue($approvedResults->contains('id', $lessonNote->id));

        // Student can download the file
        $authResponse = $this->get(route('tenant.lesson-note.file', [
            'version' => $lessonNote->latestVersion->id,
            'download' => 1,
        ]));
        $authResponse->assertOk();
    }
}
