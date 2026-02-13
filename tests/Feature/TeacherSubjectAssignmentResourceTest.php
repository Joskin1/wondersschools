<?php

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create admin user
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    // Create active session and term
    $this->session = Session::factory()->create([
        'is_active' => true,
        'start_year' => 2026,
        'end_year' => 2027,
    ]);

    $this->term = Term::factory()->create([
        'session_id' => $this->session->id,
        'is_active' => true,
        'name' => 'First Term',
        'order' => 1,
    ]);

    // Create teachers
    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
        'name' => 'Test Teacher',
    ]);

    // Create classroom and subject
    $this->classroom = Classroom::factory()->create(['name' => 'JSS 1A']);
    $this->subject = Subject::factory()->create(['name' => 'Mathematics']);

    // Authenticate as admin
    $this->actingAs($this->admin);
});

test('admin can view teacher subject assignment list page', function () {
    Livewire::test(TeacherSubjectAssignmentResource\Pages\ListTeacherSubjectAssignments::class)
        ->assertSuccessful();
});

test('admin can create teacher subject assignment', function () {
    $subject = Subject::factory()->create();
    
    Livewire::test(TeacherSubjectAssignmentResource\Pages\CreateTeacherSubjectAssignment::class)
        ->fillForm([
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ])
        ->fillForm([
            'subject_id' => $subject->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TeacherSubjectAssignment::count())->toBe(1);

    $assignment = TeacherSubjectAssignment::first();
    expect($assignment->teacher_id)->toBe($this->teacher->id)
        ->and($assignment->subject_id)->toBe($subject->id)
        ->and($assignment->classroom_id)->toBe($this->classroom->id);
});

test('admin can edit teacher subject assignment', function () {
    $assignment = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'classroom_id' => $this->classroom->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    $newSubject = Subject::factory()->create(['name' => 'English']);

    Livewire::test(TeacherSubjectAssignmentResource\Pages\EditTeacherSubjectAssignment::class, [
        'record' => $assignment->id,
    ])
        ->fillForm([
            'subject_id' => $newSubject->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($assignment->fresh()->subject_id)->toBe($newSubject->id);
});

test('admin can delete teacher subject assignment', function () {
    $assignment = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'classroom_id' => $this->classroom->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    Livewire::test(TeacherSubjectAssignmentResource\Pages\ListTeacherSubjectAssignments::class)
        ->callTableAction('delete', $assignment->id);

    expect(TeacherSubjectAssignment::count())->toBe(0);
});

test('form validates required fields', function () {
    Livewire::test(TeacherSubjectAssignmentResource\Pages\CreateTeacherSubjectAssignment::class)
        ->fillForm([
            'teacher_id' => null,
            'subject_id' => null,
            'classroom_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['teacher_id', 'subject_id', 'classroom_id']);
});

test('form defaults to active session and term', function () {
    Livewire::test(TeacherSubjectAssignmentResource\Pages\CreateTeacherSubjectAssignment::class)
        ->assertFormSet([
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);
});

test('classroom filter widget displays classrooms', function () {
    $classroom1 = Classroom::factory()->create(['name' => 'SS1']);
    $classroom2 = Classroom::factory()->create(['name' => 'SS2']);

    Livewire::test(TeacherSubjectAssignmentResource\Widgets\ClassroomFilterWidget::class)
        ->assertSee('SS1')
        ->assertSee('SS2');
});

test('classroom filter widget shows assignment counts', function () {
    $classroom = Classroom::factory()->create(['name' => 'SS1']);

    TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'classroom_id' => $classroom->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    Livewire::test(TeacherSubjectAssignmentResource\Widgets\ClassroomFilterWidget::class)
        ->assertSee('1 teacher');
});

test('can filter assignments by classroom', function () {
    $classroom1 = Classroom::factory()->create(['name' => 'SS1']);
    $classroom2 = Classroom::factory()->create(['name' => 'SS2']);

    $assignment1 = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'classroom_id' => $classroom1->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    $assignment2 = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => Subject::factory()->create()->id,
        'classroom_id' => $classroom2->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    Livewire::test(TeacherSubjectAssignmentResource\Pages\ListTeacherSubjectAssignments::class)
        ->set('classroomFilter', $classroom1->id)
        ->assertCanSeeTableRecords([$assignment1])
        ->assertCanNotSeeTableRecords([$assignment2]);
});

test('can clear classroom filter', function () {
    $classroom = Classroom::factory()->create(['name' => 'SS1']);

    $assignment = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'classroom_id' => $classroom->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    Livewire::test(TeacherSubjectAssignmentResource\Pages\ListTeacherSubjectAssignments::class)
        ->set('classroomFilter', $classroom->id)
        ->call('updateClassroomFilter', null)
        ->assertSet('classroomFilter', null);
});

test('table displays correct columns', function () {
    $assignment = TeacherSubjectAssignment::create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'classroom_id' => $this->classroom->id,
        'session_id' => $this->session->id,
        'term_id' => $this->term->id,
    ]);

    Livewire::test(TeacherSubjectAssignmentResource\Pages\ListTeacherSubjectAssignments::class)
        ->assertCanSeeTableRecords([$assignment])
        ->assertSee($this->teacher->name)
        ->assertSee($this->subject->name)
        ->assertSee($this->classroom->name)
        ->assertSee($this->session->name)
        ->assertSee($this->term->name);
});
