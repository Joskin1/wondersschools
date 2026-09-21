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

    public function test_teacher_can_create_and_edit_lesson_plan_with_topic_subtopic_and_reusable_materials(): void
    {
        $refMaterial = ReferenceMaterial::create(['name' => 'Physics for Senior Secondary 1']);
        $instMaterial = InstructionalMaterial::create(['name' => 'Prism and Light Source']);
        $teachMethod = TeachingMethod::create(['name' => 'Experimental Demonstration']);

        $plan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 5,
            'status' => 'draft',
            'topic' => 'Optics and Light Refraction',
            'sub_topic' => 'Total Internal Reflection',
            'learning_objectives' => ['Define critical angle', 'State Snell\'s law'],
        ]);

        $plan->referenceMaterials()->sync([$refMaterial->id]);
        $plan->instructionalMaterials()->sync([$instMaterial->id]);
        $plan->teachingMethods()->sync([$teachMethod->id]);

        $this->assertEquals('Optics and Light Refraction', $plan->topic);
        $this->assertEquals('Total Internal Reflection', $plan->sub_topic);
        $this->assertCount(1, $plan->referenceMaterials);
        $this->assertCount(1, $plan->instructionalMaterials);
        $this->assertCount(1, $plan->teachingMethods);

        // Edit reusable material name
        $refMaterial->update(['name' => 'Advanced Physics for SS 1']);
        $this->assertEquals('Advanced Physics for SS 1', $plan->fresh()->referenceMaterials->first()->name);

        // Add a second reusable teaching method
        $secondMethod = TeachingMethod::create(['name' => 'Group Discussion']);
        $plan->teachingMethods()->sync([$teachMethod->id, $secondMethod->id]);
        $this->assertCount(2, $plan->fresh()->teachingMethods);
    }

    public function test_submit_pair_for_review_fails_when_paired_lesson_note_missing(): void
    {
        $plan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 7,
            'status' => 'draft',
            'topic' => 'Week 7 Topic',
        ]);

        $service = app(LessonSubmissionService::class);
        $result = $service->submitPairForReview($plan);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No Lesson Note found for Week 7', $result['message']);
        $this->assertEquals('draft', $plan->fresh()->status);
    }

    public function test_submit_pair_for_review_succeeds_when_paired_lesson_note_exists(): void
    {
        Notification::fake();

        $note = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 8,
            'status' => 'draft',
            'learning_objectives' => ['Objective 1'],
        ]);

        $version = LessonNoteVersion::create([
            'lesson_note_id' => $note->id,
            'submission_type' => 'written',
            'title' => 'Week 8 Note',
            'content' => '<p>Week 8 content</p>',
            'file_name' => 'Week 8 Note',
            'file_size' => 100,
            'file_hash' => 'hash_8',
            'uploaded_by' => $this->teacher->id,
            'status' => 'draft',
        ]);
        $note->update(['latest_version_id' => $version->id]);

        $plan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 8,
            'status' => 'draft',
            'topic' => 'Week 8 Topic',
        ]);

        $service = app(LessonSubmissionService::class);
        $result = $service->submitPairForReview($plan);

        $this->assertTrue($result['success']);
        $this->assertEquals('pending', $plan->fresh()->status);
        $this->assertEquals('pending', $note->fresh()->status);
        $this->assertEquals('pending', $version->fresh()->status);

        Notification::assertSentTo(
            $this->admin,
            LessonSubmissionReady::class,
            function ($notification) use ($note, $plan) {
                return $notification->lessonNote->id === $note->id
                    && $notification->lessonPlan->id === $plan->id;
            }
        );
    }

    public function test_admin_can_download_lesson_plan_and_lesson_note_pdf(): void
    {
        $note = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 9,
            'status' => 'pending',
            'learning_objectives' => ['Learn about atoms'],
        ]);

        $version = LessonNoteVersion::create([
            'lesson_note_id' => $note->id,
            'submission_type' => 'written',
            'title' => 'Atomic Structure',
            'content' => '<p>Protons, neutrons, and electrons.</p>',
            'file_name' => 'Atomic Structure',
            'file_size' => 150,
            'file_hash' => 'hash_9',
            'uploaded_by' => $this->teacher->id,
            'status' => 'pending',
        ]);
        $note->update(['latest_version_id' => $version->id]);

        $plan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 9,
            'status' => 'pending',
            'topic' => 'Atomic Structure',
            'content' => '<p>Plan content for atomic structure.</p>',
        ]);

        // Admin downloads note PDF
        $responseNote = $this->actingAs($this->admin)
            ->get(route('admin.lesson-note.pdf', $note));
        $responseNote->assertOk();
        $responseNote->assertHeader('content-type', 'application/pdf');

        // Admin downloads plan PDF
        $responsePlan = $this->actingAs($this->admin)
            ->get(route('admin.lesson-plan.pdf', $plan));
        $responsePlan->assertOk();
        $responsePlan->assertHeader('content-type', 'application/pdf');
    }

    public function test_template_validation_service(): void
    {
        $parser = app(\App\Services\LessonDocxParserService::class);

        // Non-existent file
        $res = $parser->validateLessonNoteTemplate('/non/existent/file.docx');
        $this->assertFalse($res['valid']);

        $resPlan = $parser->validateLessonPlanTemplate('/non/existent/file.docx');
        $this->assertFalse($resPlan['valid']);
    }
}
