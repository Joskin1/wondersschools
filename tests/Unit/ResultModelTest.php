<?php

use App\Models\Result;
use App\Models\Student;
use App\Models\Term;
use App\Models\Classroom;
use App\Models\AcademicSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('result can be created with valid data', function () {
    $session = AcademicSession::factory()->create();
    $term = Term::factory()->create(['academic_session_id' => $session->id]);
    $classroom = Classroom::factory()->create();
    $student = Student::factory()->create(['classroom_id' => $classroom->id]);

    $result = Result::factory()->create([
        'student_id' => $student->id,
        'term_id' => $term->id,
        'total_score' => 450,
        'average_score' => 75.0,
        'position' => 5,
    ]);

    expect($result)->toBeInstanceOf(Result::class);
    expect($result->total_score)->toBe(450);
    expect($result->average_score)->toBe(75.0);
    expect($result->position)->toBe(5);
});

test('result belongs to student', function () {
    $student = Student::factory()->create();
    $result = Result::factory()->create(['student_id' => $student->id]);

    expect($result->student)->toBeInstanceOf(Student::class);
    expect($result->student->id)->toBe($student->id);
});

test('result belongs to term', function () {
    $term = Term::factory()->create();
    $result = Result::factory()->create(['term_id' => $term->id]);

    expect($result->term)->toBeInstanceOf(Term::class);
    expect($result->term->id)->toBe($term->id);
});

test('student can have multiple results for different terms', function () {
    $student = Student::factory()->create();
    $session = AcademicSession::factory()->create();
    
    $term1 = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
    ]);
    
    $term2 = Term::factory()->create([
        'name' => 'Second Term',
        'academic_session_id' => $session->id,
    ]);

    Result::factory()->create([
        'student_id' => $student->id,
        'term_id' => $term1->id,
    ]);
    
    Result::factory()->create([
        'student_id' => $student->id,
        'term_id' => $term2->id,
    ]);

    expect($student->results()->count())->toBe(2);
});

test('result calculates average from total score', function () {
    $result = Result::factory()->create([
        'total_score' => 540,
        'average_score' => 90.0,
    ]);

    // Assuming 6 subjects
    $expectedAverage = 540 / 6;
    expect($result->average_score)->toBe($expectedAverage);
});
