<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\InstructionalMaterial;
use App\Models\LessonNote;
use App\Models\LessonNoteVersion;
use App\Models\LessonPlan;
use App\Models\ReferenceMaterial;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\TeachingMethod;
use App\Models\Term;
use App\Models\User;
use App\Notifications\LessonSubmissionReady;
use App\Services\LessonSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LessonPlanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $admin;
    protected Session $session;
    protected Term $term;
    protected Classroom $classroom;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->session = Session::factory()->create(['is_active' => true]);
        $this->term = Term::factory()->create([
            'session_id' => $this->session->id,
            'is_active' => true,
        ]);
        $this->classroom = Classroom::factory()->create();
        $this->subject = Subject::factory()->create();
    }

    public function test_it_creates_lesson_plan_with_lookup_materials_and_methods(): void
    {
        $refMaterial = ReferenceMaterial::create(['name' => 'Essential Mathematics JSS 2']);
        $instMaterial = InstructionalMaterial::create(['name' => 'Interactive Whiteboard']);
        $teachMethod = TeachingMethod::create(['name' => 'Discovery Method']);

        $lessonPlan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 3,
            'status' => 'draft',
            'title' => 'Linear Equations',
            'time' => '40 mins',
            'section' => 'Morning',
            'learning_objectives' => ['Solve simple equations', 'Identify unknown variables'],
            'key_vocabulary' => 'Variable, Coefficient, Constant',
            'prior_knowledge' => '<p>Basic arithmetic operations</p>',
            'content' => '<p>Step-by-step method to solve 2x + 4 = 10</p>',
            'presentation_steps' => ['Introduce balancing method', 'Demonstrate 3 examples', 'Class exercises'],
            'strategies_activities' => '<p>Pair work and board practice</p>',
            'evaluation_questions' => ['Solve: 3x = 12', 'Solve: x - 5 = 7'],
            'conclusion' => '<p>Review key rules of balancing equations</p>',
            'assignment' => '<p>Exercise 4B Questions 1 to 10</p>',
        ]);

        $lessonPlan->referenceMaterials()->attach($refMaterial->id);
        $lessonPlan->instructionalMaterials()->attach($instMaterial->id);
        $lessonPlan->teachingMethods()->attach($teachMethod->id);

        $this->assertDatabaseHas('lesson_plans', [
            'id' => $lessonPlan->id,
            'week_number' => 3,
            'status' => 'draft',
            'title' => 'Linear Equations',
        ]);

        $this->assertCount(1, $lessonPlan->referenceMaterials);
        $this->assertCount(1, $lessonPlan->instructionalMaterials);
        $this->assertCount(1, $lessonPlan->teachingMethods);
    }

    public function test_dual_submission_completeness_and_admin_notification(): void
    {
        Notification::fake();

        // 1. Create Lesson Note only
        $lessonNote = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 4,
            'status' => 'pending',
        ]);

        $service = app(LessonSubmissionService::class);

        // Check completeness with only note
        $status = $service->getSubmissionStatus(
            $this->teacher->id,
            $this->subject->id,
            $this->classroom->id,
            $this->session->id,
            $this->term->id,
            4
        );
        $this->assertTrue($status['missing_plan']);
        $this->assertFalse($status['is_complete']);

        // Check notification should NOT be sent when plan is missing
        $service->checkAndNotifyIfComplete($lessonNote);
        Notification::assertNothingSent();

        // 2. Now create paired Lesson Plan
        $lessonPlan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 4,
            'status' => 'pending',
            'title' => 'Week 4 Plan',
        ]);

        // Now both are pending -> triggers notification!
        $service->checkAndNotifyIfComplete($lessonPlan);

        Notification::assertSentTo(
            $this->admin,
            LessonSubmissionReady::class,
            function ($notification) use ($lessonNote, $lessonPlan) {
                return $notification->lessonNote->id === $lessonNote->id
                    && $notification->lessonPlan->id === $lessonPlan->id;
            }
        );
    }

    public function test_unified_admin_approval_and_rejection(): void
    {
        $lessonNote = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 5,
            'status' => 'pending',
        ]);

        $version = LessonNoteVersion::create([
            'lesson_note_id' => $lessonNote->id,
            'submission_type' => 'written',
            'title' => 'Week 5 Note',
            'content' => '<p>Lesson note content</p>',
            'file_name' => 'Week 5 Note',
            'file_size' => 100,
            'file_hash' => 'dummy_hash_5',
            'uploaded_by' => $this->teacher->id,
            'status' => 'pending',
        ]);
        $lessonNote->update(['latest_version_id' => $version->id]);

        $lessonPlan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 5,
            'status' => 'pending',
            'title' => 'Week 5 Plan',
        ]);

        // Test approve
        $lessonNote->approve('Excellent work!', $this->admin->id);

        $this->assertEquals('approved', $lessonNote->fresh()->status);
        $this->assertEquals('approved', $lessonPlan->fresh()->status);
        $this->assertEquals('Excellent work!', $lessonPlan->fresh()->admin_comment);

        // Test reject
        $lessonNote->reject('Needs more practice questions', $this->admin->id);

        $this->assertEquals('rejected', $lessonNote->fresh()->status);
        $this->assertEquals('rejected', $lessonPlan->fresh()->status);
        $this->assertEquals('Needs more practice questions', $lessonPlan->fresh()->admin_comment);
    }

    public function test_student_can_download_approved_lesson_note_pdf_and_cannot_access_unapproved(): void
    {
        $studentUser = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'status' => 'active',
        ]);

        StudentEnrollment::factory()->create([
            'student_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
        ]);

        $this->classroom->subjects()->attach($this->subject->id);

        $lessonNote = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 6,
            'status' => 'approved',
        ]);

        $version = LessonNoteVersion::create([
            'lesson_note_id' => $lessonNote->id,
            'submission_type' => 'written',
            'title' => 'Photosynthesis Note',
            'content' => '<p>Complete guide to photosynthesis.</p>',
            'learning_objectives' => ['Understand light reaction', 'Understand dark reaction'],
            'file_name' => 'Photosynthesis Note',
            'file_size' => 200,
            'file_hash' => 'hash_6',
            'uploaded_by' => $this->teacher->id,
            'status' => 'approved',
        ]);
        $lessonNote->update(['latest_version_id' => $version->id]);

        // Student can download approved lesson note PDF
        $response = $this->actingAs($studentUser)
            ->get(route('student.lesson-note.pdf', $lessonNote));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        // If note is pending, student gets 403
        $lessonNote->update(['status' => 'pending']);
        $unapprovedResponse = $this->actingAs($studentUser)
            ->get(route('student.lesson-note.pdf', $lessonNote));

        $unapprovedResponse->assertForbidden();
    }

    public function test_teacher_can_access_lesson_plans_list_and_create_page(): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('teacher'));

        \Livewire\Livewire::actingAs($this->teacher)
            ->test(\App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages\ListTeacherLessonPlans::class)
            ->assertSuccessful();

        \Livewire\Livewire::actingAs($this->teacher)
            ->test(\App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages\CreateTeacherLessonPlan::class)
            ->assertSuccessful();
    }
}
