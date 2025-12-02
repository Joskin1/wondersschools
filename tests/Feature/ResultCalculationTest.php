<?php

use App\Models\AcademicSession;
use App\Models\Classroom;
use App\Models\Result;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Result Calculation', function () {
    it('calculates result when score is created', function () {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'Exam', 'max_score' => 60]);

        Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 30,
            'exam_score' => 50,
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
    });

    it('updates result when score is updated', function () {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'Exam', 'max_score' => 60]);

        $score = Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 20,
            'exam_score' => 30,
        ]);

        $score->update(['ca_score' => 30, 'exam_score' => 60]);

        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'total_score' => 90,
            'average_score' => 90,
            'grade' => 'A+',
        ]);
    });

    it('calculates average correctly with multiple subjects', function () {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();
        
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'Exam', 'max_score' => 60]);

        Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject1->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 30,
            'exam_score' => 50, // Total 80
        ]);

        Score::create([
            'student_id' => $student->id,
            'subject_id' => $subject2->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 20,
            'exam_score' => 40, // Total 60
        ]);

        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'total_score' => 140, // 80 + 60
            'average_score' => 70, // 140 / 2
            'grade' => 'B',
        ]);
    });

    it('calculates position correctly', function () {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student1 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $student2 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'Exam', 'max_score' => 60]);

        // Student 1 scores 80
        Score::create([
            'student_id' => $student1->id,
            'subject_id' => $subject->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 30,
            'exam_score' => 50,
        ]);

        // Student 2 scores 90
        Score::create([
            'student_id' => $student2->id,
            'subject_id' => $subject->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 35,
            'exam_score' => 55,
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
    });

    it('recalculates position when scores change', function () {
        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);
        $classroom = Classroom::factory()->create();
        $student1 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $student2 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'CA', 'max_score' => 40]);
        \App\Models\EvaluationSetting::create(['academic_session_id' => $session->id, 'name' => 'Exam', 'max_score' => 60]);

        // Initial: Student 1 = 80, Student 2 = 90
        Score::create([
            'student_id' => $student1->id,
            'subject_id' => $subject->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 30,
            'exam_score' => 50,
        ]);

        $score2 = Score::create([
            'student_id' => $student2->id,
            'subject_id' => $subject->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'ca_score' => 35,
            'exam_score' => 55,
        ]);

        // Verify initial positions
        expect(Result::where('student_id', $student1->id)->first()->position)->toBe(2);
        expect(Result::where('student_id', $student2->id)->first()->position)->toBe(1);

        // Update Student 2 score to 70 (now lower than Student 1)
        $score2->update(['ca_score' => 20, 'exam_score' => 50]);

        // Verify new positions
        expect(Result::where('student_id', $student1->id)->first()->position)->toBe(1);
        expect(Result::where('student_id', $student2->id)->first()->position)->toBe(2);
    });
});
