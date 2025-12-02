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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_is_calculated_when_score_is_created()
    {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        $assessmentType = AssessmentType::factory()->create(['max_score' => 100]);

        Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 80,
        ]);

        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'total_score' => 80,
            'average_score' => 80,
            'grade' => 'A',
            'position' => 1,
        ]);
    }

    public function test_result_is_updated_when_score_is_updated()
    {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        $assessmentType = AssessmentType::factory()->create(['max_score' => 100]);

        $score = Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 50,
        ]);

        $score->update(['score' => 90]);

        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'total_score' => 90,
            'average_score' => 90,
            'grade' => 'A+',
        ]);
    }

    public function test_result_calculates_average_correctly_with_multiple_subjects()
    {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();
        $assessmentType = AssessmentType::factory()->create(['max_score' => 100]);

        Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject1->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 80,
        ]);

        Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject2->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 60,
        ]);

        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'total_score' => 140, // 80 + 60
            'average_score' => 70, // 140 / 2
            'grade' => 'B',
        ]);
    }

    public function test_position_is_calculated_correctly()
    {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student1 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $student2 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        $assessmentType = AssessmentType::factory()->create(['max_score' => 100]);

        // Student 1 scores 80
        Score::create([
            'student_id' => $student1->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 80,
        ]);

        // Student 2 scores 90
        Score::create([
            'student_id' => $student2->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 90,
        ]);

        // Student 2 should be 1st, Student 1 should be 2nd
        $this->assertDatabaseHas('results', [
            'student_id' => $student2->id,
            'position' => 1,
        ]);

        $this->assertDatabaseHas('results', [
            'student_id' => $student1->id,
            'position' => 2,
        ]);
    }

    public function test_position_recalculates_when_scores_change()
    {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student1 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $student2 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        $assessmentType = AssessmentType::factory()->create(['max_score' => 100]);

        // Initial: Student 1 = 80, Student 2 = 90
        Score::create([
            'student_id' => $student1->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 80,
        ]);

        $score2 = Score::create([
            'student_id' => $student2->id,
            'subject_id' => $subject->id,
            'assessment_type_id' => $assessmentType->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'score' => 90,
        ]);

        // Verify initial positions
        $this->assertEquals(2, Result::where('student_id', $student1->id)->first()->position);
        $this->assertEquals(1, Result::where('student_id', $student2->id)->first()->position);

        // Update Student 2 score to 70 (now lower than Student 1)
        $score2->update(['score' => 70]);

        // Verify new positions
        $this->assertEquals(1, Result::where('student_id', $student1->id)->first()->position);
        $this->assertEquals(2, Result::where('student_id', $student2->id)->first()->position);
    }
}
