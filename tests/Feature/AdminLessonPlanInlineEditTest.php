<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageLessonPlanOptions;
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
use Livewire\Livewire;
use Tests\TestCase;

class AdminLessonPlanInlineEditTest extends TestCase
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

    protected function createSubmissionPair(int $week = 1, string $status = 'pending'): array
    {
        $ref1 = ReferenceMaterial::create(['name' => 'Original Textbook JSS 2']);
        $ref2 = ReferenceMaterial::create(['name' => 'Secondary Science Guide']);
        $inst1 = InstructionalMaterial::create(['name' => 'Whiteboard & Marker']);
        $method1 = TeachingMethod::create(['name' => 'Inquiry-Based Learning']);

        $note = LessonNote::create([
            'teacher_id'   => $this->teacher->id,
            'subject_id'   => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id'   => $this->session->id,
            'term_id'      => $this->term->id,
            'week_number'  => $week,
            'status'       => $status,
            'title'        => 'CELL BIOLOGY',
        ]);

        $version = LessonNoteVersion::create([
            'lesson_note_id'    => $note->id,
            'uploaded_by'       => $this->teacher->id,
            'submission_type'   => 'written',
            'title'             => 'CELL BIOLOGY',
            'content'           => '<p>Lesson note content on cell biology</p>',
            'file_name'         => 'Cell Biology Note',
            'version_number'    => 1,
            'status'            => $status,
        ]);

        $note->update(['latest_version_id' => $version->id]);

        $plan = LessonPlan::create([
            'teacher_id'            => $this->teacher->id,
            'subject_id'            => $this->subject->id,
            'classroom_id'          => $this->classroom->id,
            'session_id'            => $this->session->id,
            'term_id'               => $this->term->id,
            'week_number'           => $week,
            'status'                => $status,
            'topic'                 => 'CELL BIOLOGY',
            'sub_topic'             => 'Animal and plant cells',
            'time'                  => '45 minutes',
            'section'               => 'Period 2',
            'learning_objectives'   => ['Identify the parts of an animal cell', 'Compare plant and animal cells'],
            'key_vocabulary'        => 'Nucleus, Cytoplasm, Vacuole',
            'prior_knowledge'       => '<p>Students know living things have organs.</p>',
            'content'               => '<p>Detailed cell biology explanation.</p>',
            'presentation_steps'    => ['Step 1: Introduction', 'Step 2: Microscopic view'],
            'strategies_activities' => '<p>Group discussion and diagram labelling.</p>',
            'evaluation_questions'  => ['What is the function of the nucleus?'],
            'conclusion'            => '<p>Summary of organelles.</p>',
            'assignment'            => '<p>Draw and label a plant cell.</p>',
        ]);

        $plan->referenceMaterials()->sync([$ref1->id, $ref2->id]);
        $plan->instructionalMaterials()->sync([$inst1->id]);
        $plan->teachingMethods()->sync([$method1->id]);

        return ['note' => $note, 'version' => $version, 'plan' => $plan, 'ref1' => $ref1, 'ref2' => $ref2, 'inst1' => $inst1, 'method1' => $method1];
    }

    public function test_admin_viewing_review_page_hydrates_paired_lesson_plan_form(): void
    {
        $this->actingAs($this->admin);
        $pair = $this->createSubmissionPair();

        $test = Livewire::test(ViewLessonNote::class, ['record' => $pair['note']->id])
            ->assertOk()
            ->assertSet('planData.topic', 'CELL BIOLOGY')
            ->assertSet('planData.sub_topic', 'Animal and plant cells')
            ->assertSet('planData.time', '45 minutes')
            ->assertSet('planData.section', 'Period 2')
            ->assertSet('planData.key_vocabulary', ['Nucleus', 'Cytoplasm', 'Vacuole'])
            ->assertSet('planData.reference_materials', [$pair['ref1']->id, $pair['ref2']->id])
            ->assertSet('planData.instructional_materials', [$pair['inst1']->id])
            ->assertSet('planData.teaching_methods', [$pair['method1']->id]);

        $objectives = collect($test->get('planData.learning_objectives'))->pluck('objective')->values()->toArray();
        $this->assertEquals(['Identify the parts of an animal cell', 'Compare plant and animal cells'], $objectives);
    }

    public function test_admin_can_inline_edit_and_save_lesson_plan(): void
    {
        $this->actingAs($this->admin);
        $pair = $this->createSubmissionPair();

        $newRef = ReferenceMaterial::create(['name' => 'Advanced Biology Handbook']);

        Livewire::test(ViewLessonNote::class, ['record' => $pair['note']->id])
            ->set('planData.topic', 'Advanced Cell Biology')
            ->set('planData.sub_topic', 'mitochondria and energy')
            ->set('planData.time', '80 minutes')
            ->set('planData.learning_objectives', [
                ['objective' => 'Understand ATP production'],
                ['objective' => 'Identify mitochondrial membranes'],
            ])
            ->set('planData.key_vocabulary', ['ATP', 'Cristae', 'Matrix'])
            ->set('planData.reference_materials', [$newRef->id])
            ->set('planData.content', '<p>Updated content edited directly by admin.</p>')
            ->call('savePlan')
            ->assertHasNoErrors()
            ->assertNotified('Lesson Plan Saved');

        $pair['plan']->refresh();
        $this->assertEquals('ADVANCED CELL BIOLOGY', $pair['plan']->topic);
        $this->assertEquals('Mitochondria and energy', $pair['plan']->sub_topic);
        $this->assertEquals('80 minutes', $pair['plan']->time);
        $this->assertEquals(['Understand ATP production', 'Identify mitochondrial membranes'], $pair['plan']->learning_objectives);
        $this->assertEquals('ATP, Cristae, Matrix', $pair['plan']->key_vocabulary);
        $this->assertEquals('<p>Updated content edited directly by admin.</p>', $pair['plan']->content);
        $this->assertEquals([$newRef->id], $pair['plan']->referenceMaterials->pluck('id')->toArray());

        // Asserts paired lesson note version title also updated
        $pair['version']->refresh();
        $this->assertEquals('ADVANCED CELL BIOLOGY', $pair['version']->title);
    }

    public function test_admin_inline_edit_and_approve_autosaves_changes(): void
    {
        $this->actingAs($this->admin);
        $pair = $this->createSubmissionPair();

        Livewire::test(ViewLessonNote::class, ['record' => $pair['note']->id])
            ->set('planData.topic', 'Corrected Cell Biology Topic')
            ->set('planData.sub_topic', 'Detailed organelle review')
            ->set('planData.assignment', '<p>Admin added a homework question.</p>')
            ->callAction('approve', [
                'comment' => 'Minor typos fixed. Approved!',
            ])
            ->assertHasNoErrors()
            ->assertNotified('Submission Approved');

        $pair['plan']->refresh();
        $pair['note']->refresh();

        // Verifies plan was saved
        $this->assertEquals('CORRECTED CELL BIOLOGY TOPIC', $pair['plan']->topic);
        $this->assertEquals('Detailed organelle review', $pair['plan']->sub_topic);
        $this->assertEquals('<p>Admin added a homework question.</p>', $pair['plan']->assignment);

        // Verifies both are marked approved
        $this->assertEquals('approved', $pair['plan']->status);
        $this->assertEquals('approved', $pair['note']->status);
        $this->assertEquals('Minor typos fixed. Approved!', $pair['plan']->admin_comment);
    }

    public function test_admin_can_manage_lesson_plan_options_page(): void
    {
        $this->actingAs($this->admin);

        // 1. Create a reference material
        Livewire::test(ManageLessonPlanOptions::class)
            ->assertOk()
            ->set('newOptionName', 'Oxford Biology for Secondary Schools')
            ->call('createOption')
            ->assertNotified('Option Created');

        $ref = ReferenceMaterial::where('name', 'Oxford Biology for Secondary Schools')->first();
        $this->assertNotNull($ref);

        // 2. Edit / Rename
        Livewire::test(ManageLessonPlanOptions::class)
            ->set('editingOptionId', $ref->id)
            ->set('editingOptionName', 'Oxford Senior Biology (Updated Edition)')
            ->call('updateOption')
            ->assertNotified('Option Renamed');

        $ref->refresh();
        $this->assertEquals('Oxford Senior Biology (Updated Edition)', $ref->name);

        // 3. Switch tab and create an Instructional Material
        Livewire::test(ManageLessonPlanOptions::class)
            ->call('setTab', 'instructional_materials')
            ->assertSet('activeTab', 'instructional_materials')
            ->set('newOptionName', '3D Cell Model')
            ->call('createOption')
            ->assertNotified('Option Created');

        $inst = InstructionalMaterial::where('name', '3D Cell Model')->first();
        $this->assertNotNull($inst);

        // 4. Switch tab and create a Teaching Method
        Livewire::test(ManageLessonPlanOptions::class)
            ->call('setTab', 'teaching_methods')
            ->assertSet('activeTab', 'teaching_methods')
            ->set('newOptionName', 'Flipped Classroom')
            ->call('createOption')
            ->assertNotified('Option Created');

        $method = TeachingMethod::where('name', 'Flipped Classroom')->first();
        $this->assertNotNull($method);

        // 5. Delete single option
        Livewire::test(ManageLessonPlanOptions::class)
            ->call('setTab', 'teaching_methods')
            ->set('deletingOptionId', $method->id)
            ->call('deleteOption')
            ->assertNotified('Option Deleted');

        $this->assertDatabaseMissing('teaching_methods', ['id' => $method->id]);

        // 6. Bulk Delete
        $m1 = InstructionalMaterial::create(['name' => 'Junk item 1']);
        $m2 = InstructionalMaterial::create(['name' => 'Junk item 2']);

        Livewire::test(ManageLessonPlanOptions::class)
            ->call('setTab', 'instructional_materials')
            ->set('selectedIds', [$m1->id, $m2->id])
            ->call('deleteSelected')
            ->assertNotified('Options Deleted');

        $this->assertDatabaseMissing('instructional_materials', ['id' => $m1->id]);
        $this->assertDatabaseMissing('instructional_materials', ['id' => $m2->id]);
    }

    public function test_deleting_dropdown_option_cascades_safely_from_pivot_tables(): void
    {
        $this->actingAs($this->admin);
        $pair = $this->createSubmissionPair();

        $refToDelete = $pair['ref1'];

        // Confirm pivot exists
        $this->assertDatabaseHas('lesson_plan_reference_materials', [
            'lesson_plan_id'        => $pair['plan']->id,
            'reference_material_id' => $refToDelete->id,
        ]);

        // Delete the option via ManageLessonPlanOptions
        Livewire::test(ManageLessonPlanOptions::class)
            ->set('activeTab', 'reference_materials')
            ->set('deletingOptionId', $refToDelete->id)
            ->call('deleteOption');

        // Confirm option is gone
        $this->assertDatabaseMissing('reference_materials', ['id' => $refToDelete->id]);

        // Confirm pivot was cleanly cascaded by DB without deleting lesson plan
        $this->assertDatabaseMissing('lesson_plan_reference_materials', [
            'reference_material_id' => $refToDelete->id,
        ]);

        // Confirm lesson plan itself is intact and has remaining reference material
        $pair['plan']->refresh();
        $this->assertNotNull($pair['plan']);
        $this->assertEquals([$pair['ref2']->id], $pair['plan']->referenceMaterials->pluck('id')->toArray());
    }

    public function test_teacher_cannot_access_manage_lesson_plan_options(): void
    {
        $this->actingAs($this->teacher);

        $this->assertFalse(ManageLessonPlanOptions::canAccess());
    }
}
