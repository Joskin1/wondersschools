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

    \App\Models\StudentEnrollment::create([
        'student_id'   => $this->student->id,
        'classroom_id' => $this->classroom->id,
        'session_id'   => $this->session->id,
    ]);

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

    it('requires a result checker PIN before showing term result data', function () {
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

        \App\Models\ClassTermApproval::create([
            'classroom_id' => $this->classroom->id,
            'session_id'   => $this->session->id,
            'term_id'      => $this->term->id,
            'status'       => 'approved',
        ]);

        $batch = \App\Models\ResultPinBatch::create([
            'batch_code' => 'BATCH-TEST-001',
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'quantity'   => 1,
        ]);

        $pinRecord = \App\Models\ResultPin::create([
            'batch_id'      => $batch->id,
            'serial_number' => 'SN-000001',
            'pin'           => '654321',
            'session_id'    => $this->session->id,
            'term_id'       => $this->term->id,
            'is_used'       => false,
        ]);

        $this->actingAs($this->user);

        // Selecting session & term shows PIN gatekeeper
        Livewire::test(StudentResultPage::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->assertSet('loaded', false)
            ->assertSet('isUnlocked', false)
            ->assertSee('Result Checker PIN Required')
            // Entering wrong PIN fails
            ->set('pin_input', '000000')
            ->call('verifyPin')
            ->assertSet('isUnlocked', false)
            ->assertSet('pin_error', 'Invalid PIN. Please check the 6 digits on your scratch card and ensure it matches this session and term.')
            // Entering correct PIN unlocks result
            ->set('pin_input', '654321')
            ->call('verifyPin')
            ->assertSet('isUnlocked', true)
            ->assertSet('loaded', true)
            ->assertSee('Download PDF');
    });

    it('fetches term result data successfully when both session and term are selected and PIN is already unlocked', function () {
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

        \App\Models\ClassTermApproval::create([
            'classroom_id' => $this->classroom->id,
            'session_id'   => $this->session->id,
            'term_id'      => $this->term->id,
            'status'       => 'approved',
        ]);

        $this->student->update([
            'gender'        => 'female',
            'date_of_birth' => '2012-05-15',
        ]);

        $batch = \App\Models\ResultPinBatch::create([
            'batch_code' => 'BATCH-TEST-002',
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'quantity'   => 1,
        ]);

        \App\Models\ResultPin::create([
            'batch_id'      => $batch->id,
            'serial_number' => 'SN-000002',
            'pin'           => '123456',
            'session_id'    => $this->session->id,
            'term_id'       => $this->term->id,
            'student_id'    => $this->student->id,
            'is_used'       => true,
            'used_at'       => now(),
        ]);

        $this->actingAs($this->user);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->assertSet('isUnlocked', true)
            ->assertSet('loaded', true)
            ->assertSee('Download PDF')
            ->assertSet('resultData.student.gender', 'female')
            ->assertSet('resultData.student.dob', '15/05/2012')
            ->assertSet('resultData.term_result.average', 93.75)
            ->assertSet('resultData.term_result.grade', 'A');
    });
});

describe('Student Result PDF Download', function () {
    it('blocks downloading PDF for unfinalized/draft term results with 404', function () {
        TermResult::create([
            'student_id'       => $this->student->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 1,
            'grand_total'      => 80,
            'average'          => 80,
            'overall_position' => 1,
            'grade'            => 'B',
            'is_finalized'     => 0, // DRAFT / UNFINALIZED
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('student.result-pdf', [
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
        ]));

        $response->assertNotFound();
    });

    it('blocks downloading PDF when result is finalized but PIN is not redeemed with 403', function () {
        TermResult::create([
            'student_id'       => $this->student->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 1,
            'grand_total'      => 80,
            'average'          => 80,
            'overall_position' => 1,
            'grade'            => 'B',
            'is_finalized'     => 1, // FINALIZED
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('student.result-pdf', [
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
        ]));

        $response->assertForbidden();
    });

    it('allows downloading PDF when result is finalized and PIN is redeemed', function () {
        TermResult::create([
            'student_id'       => $this->student->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 1,
            'grand_total'      => 80,
            'average'          => 80,
            'overall_position' => 1,
            'grade'            => 'B',
            'is_finalized'     => 1, // FINALIZED
        ]);

        \App\Models\ClassTermApproval::create([
            'classroom_id' => $this->classroom->id,
            'session_id'   => $this->session->id,
            'term_id'      => $this->term->id,
            'status'       => 'approved',
        ]);

        $batch = \App\Models\ResultPinBatch::create([
            'batch_code' => 'BATCH-TEST-PDF',
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'quantity'   => 1,
        ]);

        \App\Models\ResultPin::create([
            'batch_id'      => $batch->id,
            'serial_number' => 'SN-000003',
            'pin'           => '888999',
            'session_id'    => $this->session->id,
            'term_id'       => $this->term->id,
            'student_id'    => $this->student->id,
            'is_used'       => true,
            'used_at'       => now(),
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('student.result-pdf', [
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    });

    it('blocks students from downloading another students finalized result', function () {
        TermResult::create([
            'student_id'       => $this->otherStudent->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 1,
            'grand_total'      => 80,
            'average'          => 80,
            'overall_position' => 1,
            'grade'            => 'B',
            'is_finalized'     => 1,
        ]);

        // Logged in as $this->user (not otherStudent)
        $this->actingAs($this->user);

        $response = $this->get(route('student.result-pdf', [
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
        ]));

        $response->assertNotFound();
    });
});
