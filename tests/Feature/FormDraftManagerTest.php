<?php

namespace Tests\Feature;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages\CreateTeacherLessonNote;
use App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages\EditTeacherLessonNote;
use App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages\CreateTeacherLessonPlan;
use App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages\EditTeacherLessonPlan;
use App\Models\Classroom;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormDraftManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Classroom $classroom;
    private Subject $subject;
    private Session $session;
    private Term $term;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->classroom = Classroom::factory()->create();
        $this->subject = Subject::factory()->create();
        $this->session = Session::factory()->create(['is_active' => true]);
        $this->term = Term::factory()->create([
            'session_id' => $this->session->id,
            'is_active' => true,
        ]);

        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('teacher'));
    }

    public function test_create_lesson_note_page_renders_form_draft_manager(): void
    {
        $this->actingAs($this->teacher);

        Livewire::test(CreateTeacherLessonNote::class)
            ->assertSuccessful()
            ->assertSee('Auto-draft protection active')
            ->assertSee('wonders_draft_lesson_note');
    }

    public function test_create_lesson_plan_page_renders_form_draft_manager(): void
    {
        $this->actingAs($this->teacher);

        Livewire::test(CreateTeacherLessonPlan::class)
            ->assertSuccessful()
            ->assertSee('Auto-draft protection active')
            ->assertSee('wonders_draft_lesson_plan');
    }

    public function test_edit_lesson_note_page_renders_form_draft_manager(): void
    {
        $this->actingAs($this->teacher);

        $note = LessonNote::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 1,
            'status' => 'pending',
            'learning_objectives' => ['Understand cell structure'],
        ]);

        Livewire::test(EditTeacherLessonNote::class, ['record' => $note->id])
            ->assertSuccessful()
            ->assertSee('Auto-draft protection active')
            ->assertSee("wonders_draft_lesson_note_{$this->teacher->id}_edit_{$note->id}");
    }

    public function test_edit_lesson_plan_page_renders_form_draft_manager(): void
    {
        $this->actingAs($this->teacher);

        $plan = LessonPlan::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 1,
            'status' => 'draft',
            'learning_objectives' => ['Objective 1'],
            'presentation_steps' => ['Step 1'],
            'evaluation_questions' => ['Question 1'],
        ]);

        Livewire::test(EditTeacherLessonPlan::class, ['record' => $plan->id])
            ->assertSuccessful()
            ->assertSee('Auto-draft protection active')
            ->assertSee("wonders_draft_lesson_plan_{$this->teacher->id}_edit_{$plan->id}");
    }
}
