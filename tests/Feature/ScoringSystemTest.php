<?php

namespace Tests\Feature;

use App\Models\AssessmentType;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Resources\AssessmentTypeResource;
use App\Filament\Resources\ScoreResource;

class ScoringSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_assessment_type()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AssessmentTypeResource\Pages\CreateAssessmentType::class)
            ->fillForm([
                'name' => 'Test 1',
                'max_score' => 20,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('assessment_types', [
            'name' => 'Test 1',
            'max_score' => 20,
            'is_active' => true,
        ]);
    }

    public function test_cannot_exceed_100_total_max_score()
    {
        $user = User::factory()->create();
        
        AssessmentType::create(['name' => 'Existing', 'max_score' => 80, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(AssessmentTypeResource\Pages\CreateAssessmentType::class)
            ->fillForm([
                'name' => 'New',
                'max_score' => 30, // 80 + 30 = 110 > 100
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['max_score']);
    }

    public function test_can_create_score()
    {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create(['is_current' => true]);
        $term = \App\Models\Term::factory()->create(['academic_session_id' => $session->id, 'is_current' => true]);
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();
        $assessmentType = AssessmentType::create(['name' => 'Test 1', 'max_score' => 20, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ScoreResource\Pages\CreateScore::class)
            ->fillForm([
                'academic_session_id' => $session->id,
                'term_id' => $term->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'assessment_type_id' => $assessmentType->id,
                'score' => 15,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('scores', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'score' => 15,
        ]);
    }

    public function test_score_cannot_exceed_max_score()
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();
        $assessmentType = AssessmentType::create(['name' => 'Test 1', 'max_score' => 20, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ScoreResource\Pages\CreateScore::class)
            ->fillForm([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'assessment_type_id' => $assessmentType->id,
                'score' => 25, // 25 > 20
            ])
            ->call('create')
            ->assertHasFormErrors(['score']);
    }

    public function test_can_assign_staff_as_class_teacher()
    {
        $user = User::factory()->create();
        $staff = \App\Models\Staff::factory()->create();
        
        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\Classrooms\Pages\CreateClassroom::class)
            ->fillForm([
                'name' => 'Year 1',
                'staff_id' => $staff->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('classrooms', [
            'name' => 'Year 1',
            'staff_id' => $staff->id,
        ]);
    }

    public function test_classroom_has_teacher_relationship()
    {
        $staff = \App\Models\Staff::factory()->create();
        $classroom = \App\Models\Classroom::factory()->create(['staff_id' => $staff->id]);

        $this->assertInstanceOf(\App\Models\Staff::class, $classroom->teacher);
        $this->assertEquals($staff->id, $classroom->teacher->id);
    }

    public function test_staff_has_classrooms_relationship()
    {
        $staff = \App\Models\Staff::factory()->create();
        $classroom1 = \App\Models\Classroom::factory()->create(['staff_id' => $staff->id]);
        $classroom2 = \App\Models\Classroom::factory()->create(['staff_id' => $staff->id]);

        $this->assertCount(2, $staff->classrooms);
        $this->assertTrue($staff->classrooms->contains($classroom1));
        $this->assertTrue($staff->classrooms->contains($classroom2));
    }

    public function test_student_full_name_accessor()
    {
        $student = Student::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $student->full_name);
    }

    public function test_bulk_score_input_page_loads()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ScoreResource\Pages\BulkScoreInput::class)
            ->assertSuccessful();
    }

    public function test_bulk_score_input_saves_scores()
    {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create(['is_current' => true]);
        $term = \App\Models\Term::factory()->create(['academic_session_id' => $session->id, 'is_current' => true]);
        $staff = \App\Models\Staff::factory()->create(['user_id' => $user->id]);
        $classroom = \App\Models\Classroom::factory()->create(['staff_id' => $staff->id]);
        $student1 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $student2 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        $assessmentType1 = AssessmentType::create(['name' => 'Test 1', 'max_score' => 20, 'is_active' => true]);
        $assessmentType2 = AssessmentType::create(['name' => 'Exam', 'max_score' => 80, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ScoreResource\Pages\BulkScoreInput::class)
            ->set('academicSessionId', $session->id)
            ->set('termId', $term->id)
            ->set('classroomId', $classroom->id)
            ->set('subjectId', $subject->id)
            ->call('loadStudents')
            ->set('scores', [
                $student1->id => [
                    $assessmentType1->id => 15,
                    $assessmentType2->id => 70,
                ],
                $student2->id => [
                    $assessmentType1->id => 18,
                    $assessmentType2->id => 75,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('scores', [
            'student_id' => $student1->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType1->id,
            'score' => 15,
        ]);

        $this->assertDatabaseHas('scores', [
            'student_id' => $student2->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType2->id,
            'score' => 75,
        ]);
    }
}
