<?php

use App\Models\Student;
use App\Models\Result;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Student Portal', function () {
    it('allows student to view their results', function () {
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create([
            'admission_number' => 'TEST001',
            'password' => 'password',
            'classroom_id' => $classroom->id,
        ]);

        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);

        $result = Result::factory()->create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
        ]);

        Livewire::actingAs($student, 'student')
            ->test(\App\Filament\Student\Pages\ViewResult::class, [
                'record' => $result->getRouteKey(),
            ])
            ->assertSuccessful()
            ->assertSee($session->name)
            ->assertSee($term->name);
    });

    it('prevents student from viewing other students results', function () {
        $classroom = Classroom::factory()->create();
        $student1 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $student2 = Student::factory()->create(['classroom_id' => $classroom->id]);

        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);

        $result1 = Result::factory()->create([
            'student_id' => $student1->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
        ]);

        $result2 = Result::factory()->create([
            'student_id' => $student2->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
        ]);

        Livewire::actingAs($student1, 'student')
            ->test(\App\Filament\Student\Pages\ViewResult::class, [
                'record' => $result1->getRouteKey(),
            ])
            ->assertSuccessful()
            ->assertDontSee($result2->total_score);
    });

    it('allows student to login with admission number', function () {
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create([
            'admission_number' => 'STD/2024/001',
            'password' => 'password',
            'classroom_id' => $classroom->id,
        ]);

        Livewire::test(\App\Filament\Student\Pages\Auth\Login::class)
            ->fillForm([
                'admission_number' => 'STD/2024/001',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($student, 'student');
    });
});
