<?php

namespace Tests\Feature;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages\CreateTeacherLessonNote;
use App\Models\Classroom;
use App\Models\ClassTeacherAssignment;
use App\Models\LessonNote;
use App\Models\LessonNoteVersion;
use App\Models\Session;
use App\Models\Subject;
use App\Models\SubmissionWindow;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WrittenLessonNoteTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected Subject $subject;
    protected Classroom $classroom;
    protected Session $session;
    protected Term $term;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->session = Session::factory()->create(['is_active' => true]);
        $this->term = Term::factory()->create(['session_id' => $this->session->id, 'is_active' => true]);

        $this->teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $this->subject = Subject::factory()->create();
        $this->classroom = Classroom::factory()->create();

        // Assign teacher
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        // Register week 1 for the active term.
        SubmissionWindow::create([
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 1,
        ]);

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('teacher'));
    }

    public function test_teacher_can_submit_written_lesson_note_with_images()
    {
        $this->actingAs($this->teacher);

        $image1 = UploadedFile::fake()->image('diagram1.png', 400, 300)->size(2048); // 2MB
        $image2 = UploadedFile::fake()->image('chart.jpg', 600, 400)->size(3072); // 3MB

        Livewire::test(CreateTeacherLessonNote::class)
            ->set('data.subject_id', $this->subject->id)
            ->set('data.classroom_id', $this->classroom->id)
            ->set('data.week_number', 1)
            ->set('data.submission_type', 'written')
            ->set('data.title', 'Introduction to Plant Nutrition')
            ->set('data.content', '<p>Lesson objectives: Students should be able to explain photosynthesis.</p>')
            ->set('data.images', [$image1, $image2])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert LessonNote created
        $lessonNote = LessonNote::where('teacher_id', $this->teacher->id)
            ->where('subject_id', $this->subject->id)
            ->where('classroom_id', $this->classroom->id)
            ->where('week_number', 1)
            ->first();

        $this->assertNotNull($lessonNote);
        $this->assertEquals('pending', $lessonNote->status);
        $this->assertNotNull($lessonNote->latest_version_id);

        // Assert LessonNoteVersion created with written content
        $version = $lessonNote->latestVersion;
        $this->assertNotNull($version);
        $this->assertTrue($version->isWritten());
        $this->assertEquals('written', $version->submission_type);
        $this->assertEquals('Introduction to Plant Nutrition', $version->title);
        $this->assertStringContainsString('photosynthesis', $version->content);
        $this->assertCount(2, $version->images);
        $this->assertCount(2, $version->getImageUrls());
    }

    public function test_version_helpers_work_correctly()
    {
        $version = new LessonNoteVersion([
            'submission_type' => 'written',
            'title' => 'Test Topic',
            'content' => '<h1>Topic Content</h1>',
            'images' => ['lesson-notes/images/img1.png'],
        ]);

        $this->assertTrue($version->isWritten());
        $this->assertFalse($version->isFile());
        $this->assertCount(1, $version->getImageUrls());
    }

    public function test_admin_can_view_written_lesson_note_in_admin_review_without_500()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        $note = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 1,
            'status' => 'pending',
        ]);

        $version = LessonNoteVersion::create([
            'lesson_note_id' => $note->id,
            'submission_type' => 'written',
            'title' => 'Sample Written Topic',
            'content' => '<p>Some written content here</p>',
            'file_path' => null,
            'uploaded_by' => $this->teacher->id,
            'status' => 'pending',
        ]);

        $note->update(['latest_version_id' => $version->id]);

        // Test List table page renders cleanly and sees record
        Livewire::test(\App\Filament\Resources\LessonNoteResource\Pages\ListLessonNotes::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$note]);

        // Test View page renders cleanly
        Livewire::test(\App\Filament\Resources\LessonNoteResource\Pages\ViewLessonNote::class, [
            'record' => $note->id,
        ])
            ->assertSuccessful()
            ->assertSee('Sample Written Topic')
            ->assertSee('Some written content here');
    }
}

