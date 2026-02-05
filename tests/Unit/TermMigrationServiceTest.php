<?php

use App\Models\Term;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\AcademicSession;
use App\Services\TermMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user for authentication
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);
    
    $this->service = new TermMigrationService();
    
    // Create classrooms
    Classroom::factory()->create(['name' => 'Reception']);
    Classroom::factory()->create(['name' => 'Year 1']);
    Classroom::factory()->create(['name' => 'Year 2']);
    Classroom::factory()->create(['name' => 'Year 3']);
    Classroom::factory()->create(['name' => 'Year 4']);
    Classroom::factory()->create(['name' => 'Year 5']);
    Classroom::factory()->create(['name' => 'Year 6']);
});

test('can migrate from First Term to Second Term', function () {
    $session = AcademicSession::factory()->create(['name' => '2024/2025']);
    $currentTerm = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
        'is_current' => true,
    ]);

    $result = $this->service->migrateTerm($currentTerm, 'Second Term');

    expect($result['success'])->toBeTrue();
    expect($result['old_term'])->toBe('First Term');
    expect($result['new_term'])->toBe('Second Term');
    expect($result['new_session'])->toBeFalse();
    
    // Verify old term is no longer current
    expect($currentTerm->fresh()->is_current)->toBeFalse();
    
    // Verify new term exists and is current
    $newTerm = Term::where('name', 'Second Term')->where('is_current', true)->first();
    expect($newTerm)->not->toBeNull();
});

test('can migrate from Second Term to Third Term', function () {
    $session = AcademicSession::factory()->create(['name' => '2024/2025']);
    $currentTerm = Term::factory()->create([
        'name' => 'Second Term',
        'academic_session_id' => $session->id,
        'is_current' => true,
    ]);

    $result = $this->service->migrateTerm($currentTerm, 'Third Term');

    // Assuming 6 subjects
    $expectedAverage = 540 / 6;
    expect($result->average_score)->toBe(90.0); // Float comparison
    expect($result['new_term'])->toBe('Third Term');
    expect($result['new_session'])->toBeFalse();
});

test('can migrate from Third Term to First Term with student promotion', function () {
    $session = AcademicSession::factory()->create(['name' => '2024/2025']);
    $currentTerm = Term::factory()->create([
        'name' => 'Third Term',
        'academic_session_id' => $session->id,
        'is_current' => true,
    ]);

    $reception = Classroom::where('name', 'Reception')->first();
    $year6 = Classroom::where('name', 'Year 6')->first();
    
    // Create students in different classrooms
    $studentInReception = Student::factory()->create(['classroom_id' => $reception->id]);
    $studentInYear6 = Student::factory()->create(['classroom_id' => $year6->id]);

    $result = $this->service->migrateTerm($currentTerm, 'First Term');

    expect($result['success'])->toBeTrue();
    expect($result['old_term'])->toBe('Third Term');
    expect($result['new_term'])->toBe('First Term');
    expect($result['new_session'])->toBeTrue();
    expect($result['students_promoted'])->toBe(1);
    expect($result['students_graduated'])->toBe(1);
    
    // Verify student was promoted
    $studentInReception->refresh();
    expect($studentInReception->classroom->name)->toBe('Year 1');
    
    // Verify Year 6 student was graduated
    $studentInYear6->refresh();
    expect($studentInYear6->is_graduated)->toBeTrue();
    
    // Verify new session was created
    $newSession = AcademicSession::where('name', '2025/2026')->first();
    expect($newSession)->not->toBeNull();
});

test('throws exception for invalid migration from First Term to Third Term', function () {
    $session = AcademicSession::factory()->create();
    $currentTerm = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
        'is_current' => true,
    ]);

    $this->service->migrateTerm($currentTerm, 'Third Term');
})->throws(\Exception::class, 'You cannot migrate to this term. Please follow the term sequence.');

test('throws exception for invalid migration from Second Term to First Term', function () {
    $session = AcademicSession::factory()->create();
    $currentTerm = Term::factory()->create([
        'name' => 'Second Term',
        'academic_session_id' => $session->id,
        'is_current' => true,
    ]);

    $this->service->migrateTerm($currentTerm, 'First Term');
})->throws(\Exception::class, 'You cannot migrate to this term. Please follow the term sequence.');

test('migration creates log entry', function () {
    $session = AcademicSession::factory()->create();
    $currentTerm = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
        'is_current' => true,
    ]);

    $this->service->migrateTerm($currentTerm, 'Second Term');

    $log = \App\Models\TermMigrationLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->old_term_name)->toBe('First Term');
    expect($log->new_term_name)->toBe('Second Term');
});

test('get validation error returns correct message for invalid migration', function () {
    $session = AcademicSession::factory()->create();
    $currentTerm = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
    ]);

    $error = $this->service->getValidationError($currentTerm, 'Third Term');
    
    expect($error)->toBe('You cannot migrate to this term. Please follow the term sequence.');
});

test('get validation error returns empty string for valid migration', function () {
    $session = AcademicSession::factory()->create();
    $currentTerm = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
    ]);

    $error = $this->service->getValidationError($currentTerm, 'Second Term');
    
    expect($error)->toBe('');
});
