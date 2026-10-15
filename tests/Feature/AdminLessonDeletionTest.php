<?php

namespace Tests\Feature;

use App\Filament\Resources\LessonNoteResource;
use App\Filament\Resources\LessonNoteResource\Pages\ListLessonNotes;
use App\Filament\Resources\LessonNoteResource\Pages\ViewLessonNote;
use App\Models\Classroom;
use App\Models\InstructionalMaterial;
use App\Models\LessonNote;
use App\Models\LessonNoteVersion;
use App\Models\LessonPlan;
use App\Models\ReferenceMaterial;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeachingMethod;
use App\Models\Term;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLessonDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected Session $session;
    protected Term $term;
    protected Classroom $classroom;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('lesson_notes');

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'allowed_admin_pages' => ['*'],
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->session = Session::factory()->create(['is_active' => true]);
        $this->term = Term::factory()->create([
            'session_id' => $this->session->id,
            'is_active' => true,
        ]);
        $this->classroom = Classroom::factory()->create();
        $this->subject = Subject::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function createSubmissionPair(int $week = 1, string $status = 'pending', string $submissionType = 'written'): array
    {
        $note = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => $week,
            'status' => $status,
        ]);

        $filePath = null;
        if ($submissionType === 'file') {
            $fakeFile = UploadedFile::fake()->create('biology_notes.docx', 250, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            $filePath = LessonNoteVersion::buildStoragePath(
                $this->teacher->id,
                $this->subject->id,
                $this->classroom->id,
                $this->session->id,
                $this->term->id,
                $week,
                'biology_notes.docx'
            );
            Storage::disk('lesson_notes')->put($filePath, $fakeFile->getContent());
        }

        $version = LessonNoteVersion::create([
            'lesson_note_id' => $note->id,
            'submission_type' => $submissionType,
            'title' => 'Cell Structure Week ' . $week,
            'content' => $submissionType === 'written' ? '<p>Detailed cell biology content</p>' : null,
            'file_path' => $filePath,
            'file_name' => $submissionType === 'file' ? 'biology_notes.docx' : 'Cell Structure Week ' . $week,
            'original_filename' => $submissionType === 'file' ? 'biology_notes.docx' : null,
            'file_size' => 250,
            'file_hash' => 'dummy_hash_' . $week,
            'mime_type' => $submissionType === 'file' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : null,
            'uploaded_by' => $this->teacher->id,
            'status' => $status,
        ]);
        $note->update(['latest_version_id' => $version->id]);

        $plan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => $week,
            'status' => $status,
            'title' => 'CELL STRUCTURE',
            'topic' => 'CELL STRUCTURE',
            'learning_objectives' => ['Identify parts of a cell'],
            'content' => '<p>Plan content</p>',
        ]);

        $ref = ReferenceMaterial::create(['name' => 'Bio Book ' . $week]);
        $inst = InstructionalMaterial::create(['name' => 'Microscope ' . $week]);
        $method = TeachingMethod::create(['name' => 'Lab Work ' . $week]);

        $plan->referenceMaterials()->attach($ref->id);
        $plan->instructionalMaterials()->attach($inst->id);
        $plan->teachingMethods()->attach($method->id);

        return ['note' => $note, 'version' => $version, 'plan' => $plan];
    }

    public function test_admin_can_delete_lesson_note_even_when_approved_and_teacher_can_start_again(): void
    {
        $this->actingAs($this->admin);

        // Created as approved
        ['note' => $note, 'version' => $version, 'plan' => $plan] = $this->createSubmissionPair(
            week: 2,
            status: 'approved',
            submissionType: 'file'
        );

        $filePath = $version->file_path;
        $this->assertTrue(Storage::disk('lesson_notes')->exists($filePath));

        $testable = Livewire::test(ViewLessonNote::class, ['record' => $note->id])
            ->assertActionExists('delete_note');

        // Verify confirmation configuration
        $action = $testable->instance()->getAction('delete_note');
        $this->assertTrue($action->isConfirmationRequired());
        $this->assertEquals('Delete Lesson Note?', $action->getModalHeading());

        $testable->callAction('delete_note');

        // Assert file removed from storage
        $this->assertFalse(Storage::disk('lesson_notes')->exists($filePath));

        // Assert note and version are permanently deleted (forceDeleted)
        $this->assertDatabaseMissing('lesson_notes', ['id' => $note->id]);
        $this->assertDatabaseMissing('lesson_note_versions', ['id' => $version->id]);

        // Assert paired plan was reverted to draft
        $this->assertEquals('draft', $plan->fresh()->status);

        // Critical: Teacher can now start again and create a new Lesson Note for the same week without unique violation
        $newNote = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 2,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('lesson_notes', ['id' => $newNote->id, 'week_number' => 2]);
    }

    public function test_admin_can_delete_lesson_plan_even_when_rejected_and_teacher_can_start_again(): void
    {
        $this->actingAs($this->admin);

        // Created as rejected
        ['note' => $note, 'version' => $version, 'plan' => $plan] = $this->createSubmissionPair(
            week: 3,
            status: 'rejected',
            submissionType: 'written'
        );

        $testable = Livewire::test(ViewLessonNote::class, ['record' => $note->id])
            ->assertActionExists('delete_plan');

        // Verify confirmation configuration
        $action = $testable->instance()->getAction('delete_plan');
        $this->assertTrue($action->isConfirmationRequired());
        $this->assertEquals('Delete Paired Lesson Plan?', $action->getModalHeading());

        $testable->callAction('delete_plan');

        // Assert plan is forceDeleted and relationships are detached
        $this->assertDatabaseMissing('lesson_plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('lesson_plan_reference_materials', ['lesson_plan_id' => $plan->id]);
        $this->assertDatabaseMissing('lesson_plan_instructional_materials', ['lesson_plan_id' => $plan->id]);
        $this->assertDatabaseMissing('lesson_plan_teaching_methods', ['lesson_plan_id' => $plan->id]);

        // Assert paired note is reverted to draft
        $this->assertEquals('draft', $note->fresh()->status);
        $this->assertEquals('draft', $version->fresh()->status);

        // Critical: Teacher can now create a new Lesson Plan for the same week without unique violation
        $newPlan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 3,
            'status' => 'draft',
            'title' => 'FRESH NEW TOPIC',
            'topic' => 'FRESH NEW TOPIC',
        ]);
        $this->assertDatabaseHas('lesson_plans', ['id' => $newPlan->id, 'week_number' => 3]);
    }

    public function test_admin_can_delete_both_note_and_plan(): void
    {
        $this->actingAs($this->admin);

        ['note' => $note, 'version' => $version, 'plan' => $plan] = $this->createSubmissionPair(
            week: 4,
            status: 'pending',
            submissionType: 'file'
        );

        $filePath = $version->file_path;
        $this->assertTrue(Storage::disk('lesson_notes')->exists($filePath));

        $testable = Livewire::test(ViewLessonNote::class, ['record' => $note->id])
            ->assertActionExists('delete_both');

        // Verify confirmation configuration
        $action = $testable->instance()->getAction('delete_both');
        $this->assertTrue($action->isConfirmationRequired());
        $this->assertEquals('Delete Both Lesson Note and Lesson Plan?', $action->getModalHeading());

        $testable->callAction('delete_both');

        // Both are completely gone
        $this->assertDatabaseMissing('lesson_notes', ['id' => $note->id]);
        $this->assertDatabaseMissing('lesson_note_versions', ['id' => $version->id]);
        $this->assertDatabaseMissing('lesson_plans', ['id' => $plan->id]);
        $this->assertFalse(Storage::disk('lesson_notes')->exists($filePath));

        // Teacher can create both from scratch
        $freshNote = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 4,
            'status' => 'draft',
        ]);
        $freshPlan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 4,
            'status' => 'draft',
            'title' => 'RESTARTED TOPIC',
            'topic' => 'RESTARTED TOPIC',
        ]);

        $this->assertDatabaseHas('lesson_notes', ['id' => $freshNote->id]);
        $this->assertDatabaseHas('lesson_plans', ['id' => $freshPlan->id]);
    }

    public function test_admin_can_delete_from_table_actions_in_list_page(): void
    {
        $this->actingAs($this->admin);

        ['note' => $note, 'version' => $version, 'plan' => $plan] = $this->createSubmissionPair(
            week: 5,
            status: 'pending',
            submissionType: 'written'
        );

        Livewire::test(ListLessonNotes::class)
            ->assertTableActionExists('delete_both')
            ->callTableAction('delete_both', $note);

        $this->assertDatabaseMissing('lesson_notes', ['id' => $note->id]);
        $this->assertDatabaseMissing('lesson_plans', ['id' => $plan->id]);
    }

    public function test_admin_can_bulk_delete_lesson_submissions(): void
    {
        $this->actingAs($this->admin);

        $pair1 = $this->createSubmissionPair(week: 6, status: 'pending');
        $pair2 = $this->createSubmissionPair(week: 7, status: 'pending');

        Livewire::test(ListLessonNotes::class)
            ->assertTableBulkActionExists('bulk_delete')
            ->callTableBulkAction('bulk_delete', [$pair1['note'], $pair2['note']]);

        $this->assertDatabaseMissing('lesson_notes', ['id' => $pair1['note']->id]);
        $this->assertDatabaseMissing('lesson_plans', ['id' => $pair1['plan']->id]);
        $this->assertDatabaseMissing('lesson_notes', ['id' => $pair2['note']->id]);
        $this->assertDatabaseMissing('lesson_plans', ['id' => $pair2['plan']->id]);
    }

    public function test_teacher_cannot_access_admin_deletion(): void
    {
        $this->actingAs($this->teacher);

        ['note' => $note] = $this->createSubmissionPair(week: 8, status: 'pending');

        $this->assertFalse(LessonNoteResource::canDelete($note));

        Livewire::test(ViewLessonNote::class, ['record' => $note->id])
            ->assertForbidden();
    }
}
