<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Result;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_their_results()
    {
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create([
            'admission_number' => 'TEST001',
            'password' => bcrypt('password'),
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
            ->test(\App\Filament\Student\Pages\ViewResult::class)
            ->assertSuccessful()
            ->assertSee($session->name)
            ->assertSee($term->name);
    }

    public function test_student_cannot_view_other_students_results()
    {
        $classroom = Classroom::factory()->create();
        $student1 = Student::factory()->create(['classroom_id' => $classroom->id]);
        $student2 = Student::factory()->create(['classroom_id' => $classroom->id]);

        $session = AcademicSession::factory()->create();
        $term = Term::factory()->create(['academic_session_id' => $session->id]);

        $result2 = Result::factory()->create([
            'student_id' => $student2->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
        ]);

        Livewire::actingAs($student1, 'student')
            ->test(\App\Filament\Student\Pages\ViewResult::class)
            ->assertSuccessful()
            ->assertDontSee($result2->total_score);
    }

    public function test_student_can_login_with_admission_number()
    {
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create([
            'admission_number' => 'STD/2024/001',
            'password' => bcrypt('password'),
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
    }
}
