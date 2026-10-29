<?php

use App\Filament\Resources\ResultPinBatchResource;
use App\Filament\Resources\ResultPinBatchResource\Pages\ManageResultPinBatches;
use App\Filament\Student\Pages\StudentResultPage;
use App\Models\Classroom;
use App\Models\ResultPin;
use App\Models\ResultPinBatch;
use App\Models\Session;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use STS\FilamentImpersonate\Facades\Impersonation;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = Session::factory()->create(['is_active' => true]);
    $this->term = Term::factory()->create(['session_id' => $this->session->id]);
    $this->classroom = Classroom::factory()->create();

    $this->user = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $this->student = Student::factory()->create(['user_id' => $this->user->id]);

    \App\Models\StudentEnrollment::create([
        'student_id'   => $this->student->id,
        'classroom_id' => $this->classroom->id,
        'session_id'   => $this->session->id,
    ]);

    $this->otherUser = User::factory()->create(['role' => 'student', 'is_active' => true]);
    $this->otherStudent = Student::factory()->create(['user_id' => $this->otherUser->id]);

    // Admin user (school staff)
    $this->adminUser = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    // Sudo user (platform owner)
    $this->sudoUser = User::factory()->create(['role' => 'sudo', 'is_active' => true]);
});

describe('Result Checker PIN Access Control in /admin', function () {
    it('allows access to ResultPinBatchResource only for sudo users', function () {
        $this->actingAs($this->sudoUser);
        expect(ResultPinBatchResource::canViewAny())->toBeTrue();
        expect(ResultPinBatchResource::shouldRegisterNavigation())->toBeTrue();
    });

    it('denies access to ResultPinBatchResource for school admins', function () {
        $this->actingAs($this->adminUser);
        expect(ResultPinBatchResource::canViewAny())->toBeFalse();
        expect(ResultPinBatchResource::shouldRegisterNavigation())->toBeFalse();
    });

    it('denies access to ResultPinBatchResource during administrative impersonation', function () {
        $this->actingAs($this->sudoUser);

        Impersonation::shouldReceive('isImpersonating')
            ->andReturn(true);

        expect(ResultPinBatchResource::canViewAny())->toBeFalse();
        expect(ResultPinBatchResource::shouldRegisterNavigation())->toBeFalse();
    });
});

describe('PIN Generation via Filament Admin Resource', function () {
    it('generates a batch with 40 unique 6-digit numeric PINs via ManageResultPinBatches page', function () {
        $this->actingAs($this->sudoUser);

        Livewire::test(ManageResultPinBatches::class)
            ->callAction('generate_pins', [
                'session_id' => $this->session->id,
                'term_id'    => $this->term->id,
                'quantity'   => 40,
            ])
            ->assertHasNoActionErrors();

        $batch = ResultPinBatch::where('session_id', $this->session->id)
            ->where('term_id', $this->term->id)
            ->first();

        expect($batch)->not->toBeNull();
        expect($batch->quantity)->toBe(40);
        expect(ResultPin::where('batch_id', $batch->id)->count())->toBe(40);

        // Every PIN must be 6 digits numeric
        $pins = ResultPin::where('batch_id', $batch->id)->pluck('pin');
        foreach ($pins as $pin) {
            expect($pin)->toMatch('/^\d{6}$/');
        }
    });
});

describe('Student PIN Redemption Flow', function () {
    beforeEach(function () {
        $this->termResult = TermResult::create([
            'student_id'       => $this->student->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 5,
            'grand_total'      => 450,
            'average'          => 90,
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

        $this->batch = ResultPinBatch::create([
            'batch_code' => 'BATCH-TEST-REDEMPTION',
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'quantity'   => 2,
        ]);

        $this->pin1 = ResultPin::create([
            'batch_id'      => $this->batch->id,
            'serial_number' => 'SN-RED-001',
            'pin'           => '112233',
            'session_id'    => $this->session->id,
            'term_id'       => $this->term->id,
            'is_used'       => false,
        ]);

        $this->pin2 = ResultPin::create([
            'batch_id'      => $this->batch->id,
            'serial_number' => 'SN-RED-002',
            'pin'           => '445566',
            'session_id'    => $this->session->id,
            'term_id'       => $this->term->id,
            'is_used'       => false,
        ]);
    });

    it('successfully redeems an unused PIN and locks it to the student', function () {
        $this->actingAs($this->user);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->assertSet('isUnlocked', false)
            ->set('pin_input', '112233')
            ->call('verifyPin')
            ->assertSet('isUnlocked', true)
            ->assertSet('loaded', true)
            ->assertHasNoErrors();

        $this->pin1->refresh();
        expect($this->pin1->is_used)->toBeTrue();
        expect($this->pin1->student_id)->toBe($this->student->id);
        expect($this->pin1->used_at)->not->toBeNull();
    });

    it('rejects an already used PIN when entered by another student', function () {
        $this->pin1->update([
            'is_used'    => true,
            'student_id' => $this->student->id,
            'used_at'    => now(),
        ]);

        $this->actingAs($this->otherUser);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->assertSet('isUnlocked', false)
            ->set('pin_input', '112233')
            ->call('verifyPin')
            ->assertSet('isUnlocked', false)
            ->assertSet('pin_error', 'This PIN has already been used by another student.');
    });

    it('allows a student to return and re-view their result without entering PIN again', function () {
        $this->pin1->update([
            'is_used'    => true,
            'student_id' => $this->student->id,
            'used_at'    => now(),
        ]);

        $this->actingAs($this->user);

        Livewire::test(StudentResultPage::class)
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->assertSet('isUnlocked', true)
            ->assertSet('loaded', true)
            ->assertSee('Download PDF');
    });
});

describe('Admin Impersonation Restrictions', function () {
    beforeEach(function () {
        TermResult::create([
            'student_id'       => $this->student->id,
            'session_id'       => $this->session->id,
            'term_id'          => $this->term->id,
            'classroom_id'     => $this->classroom->id,
            'subjects_count'   => 5,
            'grand_total'      => 450,
            'average'          => 90,
            'overall_position' => 1,
            'grade'            => 'A',
            'is_finalized'     => 1,
        ]);
    });

    it('blocks viewing results during administrative impersonation', function () {
        $this->actingAs($this->user);

        Impersonation::shouldReceive('isImpersonating')
            ->andReturn(true);

        Livewire::test(StudentResultPage::class)
            ->assertSet('isImpersonating', true)
            ->assertSee('Result Viewing Restricted During Impersonation')
            ->set('session_id', $this->session->id)
            ->set('term_id', $this->term->id)
            ->assertSet('loaded', false)
            ->assertSet('isUnlocked', false);
    });

    it('blocks downloading result PDF during administrative impersonation with 403', function () {
        $this->actingAs($this->user);

        Impersonation::shouldReceive('isImpersonating')
            ->andReturn(true);

        $response = $this->get(route('student.result-pdf', [
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
        ]));

        $response->assertForbidden();
    });
});

describe('Printable A4 PDF Access in Admin', function () {
    beforeEach(function () {
        $this->batch = ResultPinBatch::create([
            'batch_code' => 'BATCH-TEST-PDF-001',
            'session_id' => $this->session->id,
            'term_id'    => $this->term->id,
            'quantity'   => 2,
        ]);
    });

    it('allows sudo user to download printable A4 PDF', function () {
        $this->actingAs($this->sudoUser);

        $response = $this->get(route('admin.result-pins.pdf', ['batch' => $this->batch->id]));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    });

    it('blocks school admins from downloading result pins printable PDF with 403', function () {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.result-pins.pdf', ['batch' => $this->batch->id]));
        $response->assertForbidden();
    });
});
