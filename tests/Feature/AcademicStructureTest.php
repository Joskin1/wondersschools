<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AssessmentType;
use App\Models\Classroom;
use App\Models\Result;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_academic_session()
    {
        $session = AcademicSession::create([
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-31',
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('academic_sessions', [
            'name' => '2024/2025',
            'is_current' => true,
        ]);
    }

    public function test_can_create_term_with_session()
    {
        $session = AcademicSession::factory()->create(['is_current' => true]);
        
        $term = Term::create([
            'name' => 'First Term',
            'academic_session_id' => $session->id,
            'start_date' => '2024-09-01',
            'end_date' => '2024-12-15',
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('terms', [
            'name' => 'First Term',
            'academic_session_id' => $session->id,
            'is_current' => true,
        ]);

        $this->assertInstanceOf(AcademicSession::class, $term->academicSession);
    }

    public function test_academic_session_has_terms_relationship()
    {
        $session = AcademicSession::factory()->create();
        $term1 = Term::factory()->create(['academic_session_id' => $session->id]);
        $term2 = Term::factory()->create(['academic_session_id' => $session->id]);

        $this->assertCount(2, $session->terms);
        $this->assertTrue($session->terms->contains($term1));
        $this->assertTrue($session->terms->contains($term2));
    }

    public function test_score_belongs_to_session_and_term()
    {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();
        $assessmentType = AssessmentType::create(['name' => 'Test', 'max_score' => 20, 'is_active' => true]);

        $score = Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 15,
        ]);

        $this->assertInstanceOf(AcademicSession::class, $score->academicSession);
        $this->assertInstanceOf(Term::class, $score->term);
        $this->assertEquals($session->id, $score->academic_session_id);
        $this->assertEquals($term->id, $score->term_id);
    }

    public function test_can_create_result()
    {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $result = Result::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'total_score' => 250.5,
            'average_score' => 83.5,
            'position' => 1,
            'grade' => 'A',
            'teacher_remark' => 'Excellent performance',
            'principal_remark' => 'Keep it up',
        ]);

        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'total_score' => 250.5,
            'average_score' => 83.5,
            'position' => 1,
            'grade' => 'A',
        ]);
    }

    public function test_result_has_all_relationships()
    {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $result = Result::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'total_score' => 250.5,
            'average_score' => 83.5,
        ]);

        $this->assertInstanceOf(Student::class, $result->student);
        $this->assertInstanceOf(AcademicSession::class, $result->academicSession);
        $this->assertInstanceOf(Term::class, $result->term);
        $this->assertInstanceOf(Classroom::class, $result->classroom);
    }

    public function test_student_has_results_relationship()
    {
        $student = Student::factory()->create();
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();

        $result1 = Result::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'total_score' => 250,
            'average_score' => 83.33,
        ]);

        $result2 = Result::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'total_score' => 280,
            'average_score' => 93.33,
        ]);

        $this->assertCount(2, $student->results);
        $this->assertTrue($student->results->contains($result1));
        $this->assertTrue($student->results->contains($result2));
    }

    public function test_bulk_score_input_requires_session_and_term()
    {
        $user = User::factory()->create();
        $session = AcademicSession::factory()->create(['is_current' => true]);
        $term = Term::factory()->create(['academic_session_id' => $session->id, 'is_current' => true]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        $assessmentType = AssessmentType::create(['name' => 'Test', 'max_score' => 20, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\ScoreResource\Pages\BulkScoreInput::class)
            ->assertSet('academicSessionId', $session->id)
            ->assertSet('termId', $term->id)
            ->set('classroomId', $classroom->id)
            ->set('subjectId', $subject->id)
            ->call('loadStudents')
            ->set('scores', [
                $student->id => [
                    $assessmentType->id => 15,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('scores', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 15,
        ]);
    }
    public function test_term_resource_pages_load()
    {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create();
        $term = \App\Models\Term::factory()->create(['academic_session_id' => $session->id]);

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\TermResource\Pages\ListTerms::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\TermResource\Pages\CreateTerm::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\TermResource\Pages\EditTerm::class, [
                'record' => $term->getRouteKey(),
            ])
            ->assertSuccessful();
    }

    public function test_academic_session_resource_pages_load()
    {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\AcademicSessionResource\Pages\ListAcademicSessions::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\AcademicSessionResource\Pages\CreateAcademicSession::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\AcademicSessionResource\Pages\EditAcademicSession::class, [
                'record' => $session->getRouteKey(),
            ])
            ->assertSuccessful();
    }

    public function test_result_resource_pages_load()
    {
        $user = User::factory()->create();
        $session = \App\Models\AcademicSession::factory()->create();
        $term = \App\Models\Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = \App\Models\Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $result = \App\Models\Result::factory()->create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\ResultResource\Pages\ListResults::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\ResultResource\Pages\CreateResult::class)
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\ResultResource\Pages\EditResult::class, [
                'record' => $result->getRouteKey(),
            ])
            ->assertSuccessful();
    }
}
