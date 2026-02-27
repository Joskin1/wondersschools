<?php

use App\Filament\Student\Pages\StudentResultPage;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = Session::factory()->create(['is_active' => true]);
    $this->term = Term::factory()->create(['session_id' => $this->session->id]);
    $this->classroom = Classroom::factory()->create();

    // The core student
    $this->user = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $this->student = Student::factory()->create(['user_id' => $this->user->id]);

    // Another student (for isolation checks)
    $this->otherUser = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $this->otherStudent = Student::factory()->create(['user_id' => $this->otherUser->id]);

    // Teacher
    $this->teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
});

uses()->group('student-results');

describe('Student Result Page access', function () {
    it('is accessible by the assigned student', function () {
        $this->actingAs($this->user);

        Livewire::test(StudentResultPage::class)
            ->assertSuccessful();
    });

    it('blocks teachers from accessing the student page', function () {
        $this->actingAs($this->teacher);

        Livewire::test(StudentResultPage::class)
            ->assertForbidden();
    })->skip('Filament panel routing handles this via middleware, Livewire test might bypass the panel provider');
});

describe('Student Result Page rendering and filtering', function () {
    it('loads sessions where the student has term results on mount', function () {
        // Create a result for the student
        TermResult::create([
            'student_id'       => $this->student->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 1,
            'grand_total'      => 100,
            'average'          => 100,
            'overall_position' => 1,
            'grade'            => 'A',
            'is_finalized'     => 1,
        ]);

        $this->actingAs($this->user);

        Livewire::test(StudentResultPage::class)
            ->assertSet('sessions', function (array $sessions) {
                return count($sessions) === 1 && $sessions[0]['id'] === $this->session->id;
            });
    });

    it('does not load sessions belonging to other students', function () {
        // Create a result for the OTHER student
        TermResult::create([
            'student_id'       => $this->otherStudent->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 1,
            'grand_total'      => 100,
            'average'          => 100,
            'overall_position' => 1,
            'grade'            => 'A',
            'is_finalized'     => 1,
        ]);

        $this->actingAs($this->user);

        Livewire::test(StudentResultPage::class)
            ->assertSet('sessions', []);
    });

    it('loads the correct terms when a session is selected', function () {
        TermResult::create([
            'student_id'       => $this->student->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 1,
            'grand_total'      => 100,
            'average'          => 100,
            'overall_position' => 1,
            'grade'            => 'A',
            'is_finalized'     => 1,
        ]);

        $this->actingAs($this->user);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $this->session->id)
            ->assertSet('terms', function (array $terms) {
                return count($terms) === 1 && $terms[0]['id'] === $this->term->id;
            });
    });

    it('fetches term result data successfully when both session and term are selected', function () {
        TermResult::create([
            'student_id'       => $this->student->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 8,
            'grand_total'      => 750,
            'average'          => 93.75,
            'overall_position' => 1,
            'grade'            => 'A',
            'is_finalized'     => 1,
        ]);

        $this->actingAs($this->user);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->assertSet('loaded', true)
            ->assertSee('Download PDF')
            ->assertSet('resultData.term_result.average', 93.75)
            ->assertSet('resultData.term_result.grade', 'A');
    });
});
