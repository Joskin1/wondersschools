<?php

use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\User;
use App\Filament\Student\Resources\StudentAssignmentResource;
use App\Filament\Student\Resources\StudentAssignmentResource\Pages\TakeAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 1. Create active session and term
    $this->session = Session::createWithTerms(2026);
    $this->session->activate();
    $this->term = $this->session->terms()->where('order', 1)->first();
    $this->term->update(['is_active' => true]);

    // 2. Create Classroom and Subject
    $this->classroom = Classroom::create(['name' => 'Primary 1', 'class_order' => 1]);
    $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
    $this->classroom->subjects()->attach($this->subject->id);

    // 3. Create Student & Enrollment
    $this->studentUser = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'full_name' => 'Student One',
        'status' => 'active',
        'is_portal_active' => true,
    ]);
    StudentEnrollment::create([
        'student_id' => $this->student->id,
        'classroom_id' => $this->classroom->id,
        'session_id' => $this->session->id,
    ]);

    // 4. Create Teacher
    $this->teacher = User::factory()->create(['role' => 'teacher']);
});

function createTestAssignment(array $overrides = []): Assignment
{
    $assignment = Assignment::create(array_merge([
        'teacher_id' => test()->teacher->id,
        'subject_id' => test()->subject->id,
        'classroom_id' => test()->classroom->id,
        'session_id' => test()->session->id,
        'term_id' => test()->term->id,
        'week_number' => 1,
        'title' => 'Test Assignment',
        'description' => 'Test Description',
        'is_active' => true,
    ], $overrides));

    AssignmentQuestion::create([
        'assignment_id' => $assignment->id,
        'question_text' => 'What is 2 + 2?',
        'options' => ['A' => '3', 'B' => '4', 'C' => '5'],
        'correct_option' => 'B',
        'points' => 2,
    ]);

    AssignmentQuestion::create([
        'assignment_id' => $assignment->id,
        'question_text' => 'What is the capital of France?',
        'options' => ['A' => 'London', 'B' => 'Berlin', 'C' => 'Paris'],
        'correct_option' => 'C',
        'points' => 3,
    ]);

    return $assignment;
}

it('allows student to retrieve active assignments for their classroom and offered subjects', function () {
    $assignment = createTestAssignment();

    $this->actingAs($this->studentUser);

    $results = StudentAssignmentResource::getEloquentQuery()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($assignment->id);
});

it('hides assignments from different classrooms', function () {
    $otherClassroom = Classroom::create(['name' => 'Primary 2', 'class_order' => 2]);
    $assignment = createTestAssignment(['classroom_id' => $otherClassroom->id]);

    $this->actingAs($this->studentUser);

    $results = StudentAssignmentResource::getEloquentQuery()->get();

    expect($results)->toBeEmpty();
});

it('calculates score correctly on submission', function () {
    $assignment = createTestAssignment();
    
    // Question 1 (2 points) -> Correct is B
    // Question 2 (3 points) -> Correct is C
    $q1 = $assignment->questions[0];
    $q2 = $assignment->questions[1];

    $this->actingAs($this->studentUser);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('student'));

    Livewire::test(TakeAssignment::class, ['record' => $assignment->id])
        ->fillForm([
            "answers.{$q1->id}" => 'B', // Correct (2 points)
            "answers.{$q2->id}" => 'A', // Incorrect (0 points)
        ])
        ->call('submit');

    $submission = AssignmentSubmission::where('student_id', $this->student->id)
        ->where('assignment_id', $assignment->id)
        ->first();

    expect($submission)->not->toBeNull()
        ->and($submission->score)->toBe(2)
        ->and($submission->total_points)->toBe(5)
        ->and($submission->percentageScore())->toBe(40.0);
});

it('prevents multiple submissions for the same assignment', function () {
    $assignment = createTestAssignment();
    $q1 = $assignment->questions[0];

    $this->actingAs($this->studentUser);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('student'));

    // First submission
    Livewire::test(TakeAssignment::class, ['record' => $assignment->id])
        ->fillForm([
            "answers.{$assignment->questions[0]->id}" => 'B',
            "answers.{$assignment->questions[1]->id}" => 'C',
        ])
        ->call('submit');

    // Attempt second submission - should redirect to result page on mount
    Livewire::test(TakeAssignment::class, ['record' => $assignment->id])
        ->assertRedirect(StudentAssignmentResource::getUrl('result', ['record' => $assignment]));

    $submissions = AssignmentSubmission::where('student_id', $this->student->id)
        ->where('assignment_id', $assignment->id)
        ->get();

    // Should only have 1 submission
    expect($submissions)->toHaveCount(1)
        ->and($submissions->first()->score)->toBe(5); // From first submission (both correct)
});
