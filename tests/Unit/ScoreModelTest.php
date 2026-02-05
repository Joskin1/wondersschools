<?php

use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\Classroom;
use App\Models\AcademicSession;
use App\Models\EvaluationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('score can be created with valid data', function () {
    $session = AcademicSession::factory()->create();
    $term = Term::factory()->create(['academic_session_id' => $session->id]);
    $classroom = Classroom::factory()->create();
    $student = Student::factory()->create(['classroom_id' => $classroom->id]);
    $subject = Subject::factory()->create();

    $score = Score::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'term_id' => $term->id,
        'ca_score' => 35,
        'exam_score' => 55,
    ]);

    expect($score)->toBeInstanceOf(Score::class);
    expect($score->ca_score)->toBe(35);
    expect($score->exam_score)->toBe(55);
});

test('score belongs to student', function () {
    $student = Student::factory()->create();
    $score = Score::factory()->create(['student_id' => $student->id]);

    expect($score->student)->toBeInstanceOf(Student::class);
    expect($score->student->id)->toBe($student->id);
});

test('score belongs to subject', function () {
    $subject = Subject::factory()->create();
    $score = Score::factory()->create(['subject_id' => $subject->id]);

    expect($score->subject)->toBeInstanceOf(Subject::class);
    expect($score->subject->id)->toBe($subject->id);
});

test('score belongs to term', function () {
    $term = Term::factory()->create();
    $score = Score::factory()->create(['term_id' => $term->id]);

    expect($score->term)->toBeInstanceOf(Term::class);
    expect($score->term->id)->toBe($term->id);
});

test('score calculates total correctly', function () {
    $score = Score::factory()->create([
        'ca_score' => 30,
        'exam_score' => 60,
    ]);

    expect($score->ca_score + $score->exam_score)->toBe(90);
});

test('student can have multiple scores', function () {
    $student = Student::factory()->create();
    $subject1 = Subject::factory()->create();
    $subject2 = Subject::factory()->create();

    Score::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject1->id,
    ]);
    
    Score::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject2->id,
    ]);

    expect($student->scores()->count())->toBe(2);
});
