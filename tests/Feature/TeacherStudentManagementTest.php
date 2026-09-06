<?php

use App\Filament\Teacher\Resources\StudentResource;
use App\Filament\Teacher\Resources\StudentResource\Pages\CreateStudent;
use App\Filament\Teacher\Resources\StudentResource\Pages\ListStudents;
use App\Models\Classroom;
use App\Models\ClassTeacherAssignment;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = Session::factory()->create([
        'is_active' => true,
        'start_year' => 2026,
        'end_year' => 2027,
    ]);

    $this->classroom1 = Classroom::factory()->create(['name' => 'Grade 1']);
    $this->classroom2 = Classroom::factory()->create(['name' => 'Grade 2']);

    $this->classTeacher = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
    ]);

    $this->subjectTeacher = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
    ]);

    // Assign class teacher to classroom 1 only
    ClassTeacherAssignment::create([
        'teacher_id' => $this->classTeacher->id,
        'class_id'   => $this->classroom1->id,
        'session_id' => $this->session->id,
    ]);
});

it('authorizes only class teachers to access StudentResource in teacher panel', function () {
    $this->actingAs($this->classTeacher);
    expect(StudentResource::canAccess())->toBeTrue();

    $this->actingAs($this->subjectTeacher);
    expect(StudentResource::canAccess())->toBeFalse();
});

it('scopes student list to only students in the class teacher assigned classroom', function () {
    // Create student in classroom 1
    $student1 = Student::factory()->create(['full_name' => 'Student In Class 1']);
    StudentEnrollment::create([
        'student_id'   => $student1->id,
        'classroom_id' => $this->classroom1->id,
        'session_id'   => $this->session->id,
    ]);

    // Create student in classroom 2 (different class)
    $student2 = Student::factory()->create(['full_name' => 'Student In Class 2']);
    StudentEnrollment::create([
        'student_id'   => $student2->id,
        'classroom_id' => $this->classroom2->id,
        'session_id'   => $this->session->id,
    ]);

    $this->actingAs($this->classTeacher);

    $scopedStudents = StudentResource::getEloquentQuery()->pluck('full_name')->toArray();

    expect($scopedStudents)->toContain('Student In Class 1')
        ->and($scopedStudents)->not->toContain('Student In Class 2');
});

it('allows class teacher to create and enroll a student in their assigned classroom', function () {
    $this->actingAs($this->classTeacher);

    Livewire::test(CreateStudent::class)
        ->fillForm([
            'full_name'        => 'New Class 1 Pupil',
            'classroom_id'     => $this->classroom1->id,
            'session_id'       => $this->session->id,
            'initial_password' => 'Password123!',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $student = Student::where('full_name', 'New Class 1 Pupil')->first();
    expect($student)->not->toBeNull();

    $enrollment = StudentEnrollment::where('student_id', $student->id)->first();
    expect($enrollment)->not->toBeNull()
        ->and($enrollment->classroom_id)->toBe($this->classroom1->id)
        ->and($enrollment->session_id)->toBe($this->session->id);
});

it('prevents class teacher from creating a student in an unassigned classroom', function () {
    $this->actingAs($this->classTeacher);

    Livewire::test(CreateStudent::class)
        ->fillForm([
            'full_name'        => 'Unauthorized Pupil',
            'classroom_id'     => $this->classroom2->id,
            'session_id'       => $this->session->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['classroom_id']);

    expect(Student::where('full_name', 'Unauthorized Pupil')->exists())->toBeFalse();
});
