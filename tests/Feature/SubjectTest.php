<?php

use App\Filament\Resources\SubjectResource;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});

// ──────────────────────────────────────────────────────────────
// Database
// ──────────────────────────────────────────────────────────────

describe('Database', function () {

    it('creates a subject with valid data', function () {
        $subject = Subject::create([
            'name' => 'Mathematics',
            'code' => 'MATH',
            'is_active' => true,
        ]);

        expect($subject)->toBeInstanceOf(Subject::class)
            ->and($subject->name)->toBe('Mathematics')
            ->and($subject->code)->toBe('MATH')
            ->and($subject->is_active)->toBeTrue();
    });

    it('defaults is_active to true', function () {
        $subject = Subject::create([
            'name' => 'Physics',
            'code' => 'PHY',
        ]);

        expect($subject->fresh()->is_active)->toBeTrue();
    });

    it('prevents duplicate subject names', function () {
        Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);

        expect(fn () => Subject::create(['name' => 'Mathematics', 'code' => 'MTH']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('prevents duplicate subject codes', function () {
        Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);

        expect(fn () => Subject::create(['name' => 'Physics', 'code' => 'MATH']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('filters active subjects with scope', function () {
        Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
        Subject::create(['name' => 'Physics', 'code' => 'PHY', 'is_active' => false]);

        $active = Subject::active()->get();

        expect($active)->toHaveCount(1)
            ->and($active->first()->name)->toBe('Mathematics');
    });
});

// ──────────────────────────────────────────────────────────────
// Classroom-Subject Assignment
// ──────────────────────────────────────────────────────────────

describe('Classroom-Subject Assignment', function () {

    it('assigns a subject to a classroom', function () {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        $classroom->subjects()->attach($subject);

        expect($classroom->subjects)->toHaveCount(1)
            ->and($classroom->subjects->first()->id)->toBe($subject->id);
    });

    it('assigns multiple subjects to a classroom', function () {
        $classroom = Classroom::factory()->create();
        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();

        $classroom->subjects()->attach([$subject1->id, $subject2->id]);

        expect($classroom->subjects)->toHaveCount(2);
    });

    it('assigns a subject to multiple classrooms', function () {
        $classroom1 = Classroom::factory()->create();
        $classroom2 = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        $classroom1->subjects()->attach($subject);
        $classroom2->subjects()->attach($subject);

        expect($subject->classrooms)->toHaveCount(2);
    });

    it('prevents duplicate subject assignment to same classroom', function () {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        $classroom->subjects()->attach($subject);

        expect(fn () => $classroom->subjects()->attach($subject))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('removes subject from classroom via detach', function () {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        $classroom->subjects()->attach($subject);
        expect($classroom->subjects)->toHaveCount(1);

        $classroom->subjects()->detach($subject);
        expect($classroom->fresh()->subjects)->toHaveCount(0);
    });

    it('syncs subjects on a classroom', function () {
        $classroom = Classroom::factory()->create();
        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();
        $subject3 = Subject::factory()->create();

        $classroom->subjects()->attach([$subject1->id, $subject2->id]);
        expect($classroom->subjects)->toHaveCount(2);

        $classroom->subjects()->sync([$subject2->id, $subject3->id]);
        $classroom->refresh();

        expect($classroom->subjects)->toHaveCount(2)
            ->and($classroom->subjects->pluck('id')->toArray())->toContain($subject2->id)
            ->and($classroom->subjects->pluck('id')->toArray())->toContain($subject3->id)
            ->and($classroom->subjects->pluck('id')->toArray())->not->toContain($subject1->id);
    });
});

// ──────────────────────────────────────────────────────────────
// Filament Resource
// ──────────────────────────────────────────────────────────────

describe('Filament Resource', function () {

    it('allows admin to view subject list page', function () {
        Livewire::test(SubjectResource\Pages\ListSubjects::class)
            ->assertSuccessful();
    });

    it('displays subjects in the table', function () {
        $subject = Subject::factory()->create(['name' => 'Mathematics']);

        Livewire::test(SubjectResource\Pages\ListSubjects::class)
            ->assertCanSeeTableRecords([$subject])
            ->assertSee('Mathematics');
    });

    it('allows admin to create a subject', function () {
        Livewire::test(SubjectResource\Pages\CreateSubject::class)
            ->fillForm([
                'name' => 'Biology',
                'code' => 'BIO',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Subject::count())->toBe(1);

        $subject = Subject::first();
        expect($subject->name)->toBe('Biology')
            ->and($subject->code)->toBe('BIO')
            ->and($subject->is_active)->toBeTrue();
    });

    it('validates required fields on create', function () {
        Livewire::test(SubjectResource\Pages\CreateSubject::class)
            ->fillForm([
                'name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
    });

    it('validates unique name on create', function () {
        Subject::factory()->create(['name' => 'Mathematics']);

        Livewire::test(SubjectResource\Pages\CreateSubject::class)
            ->fillForm([
                'name' => 'Mathematics',
                'code' => 'MTH',
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
    });

    it('validates unique code on create', function () {
        Subject::factory()->create(['code' => 'MATH']);

        Livewire::test(SubjectResource\Pages\CreateSubject::class)
            ->fillForm([
                'name' => 'New Subject',
                'code' => 'MATH',
            ])
            ->call('create')
            ->assertHasFormErrors(['code']);
    });

    it('allows admin to edit a subject', function () {
        $subject = Subject::factory()->create(['name' => 'Maths']);

        Livewire::test(SubjectResource\Pages\EditSubject::class, [
            'record' => $subject->id,
        ])
            ->fillForm([
                'name' => 'Mathematics',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($subject->fresh()->name)->toBe('Mathematics');
    });

    it('allows admin to deactivate a subject', function () {
        $subject = Subject::factory()->create(['is_active' => true]);

        Livewire::test(SubjectResource\Pages\EditSubject::class, [
            'record' => $subject->id,
        ])
            ->fillForm([
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($subject->fresh()->is_active)->toBeFalse();
    });

    it('prevents non-admin users from viewing subjects', function () {
        $this->actingAs($this->teacher);

        Livewire::test(SubjectResource\Pages\ListSubjects::class)
            ->assertForbidden();
    });
});

// ──────────────────────────────────────────────────────────────
// Classroom Subject Assignment UI
// ──────────────────────────────────────────────────────────────

describe('Classroom Subject Assignment UI', function () {

    it('allows admin to assign subjects to a classroom via edit form', function () {
        $classroom = Classroom::factory()->create();
        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();

        Livewire::test(\App\Filament\Resources\ClassroomResource\Pages\EditClassroom::class, [
            'record' => $classroom->id,
        ])
            ->fillForm([
                'subjects' => [$subject1->id, $subject2->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($classroom->fresh()->subjects)->toHaveCount(2);
    });

    it('allows admin to remove subjects from a classroom via edit form', function () {
        $classroom = Classroom::factory()->create();
        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();

        $classroom->subjects()->attach([$subject1->id, $subject2->id]);

        Livewire::test(\App\Filament\Resources\ClassroomResource\Pages\EditClassroom::class, [
            'record' => $classroom->id,
        ])
            ->fillForm([
                'subjects' => [$subject1->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($classroom->fresh()->subjects)->toHaveCount(1)
            ->and($classroom->fresh()->subjects->first()->id)->toBe($subject1->id);
    });
});
