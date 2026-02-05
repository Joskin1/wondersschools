<?php

use App\Models\Term;
use App\Models\AcademicSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('term can get allowed next term for First Term', function () {
    $session = AcademicSession::factory()->create();
    $term = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
    ]);

    expect($term->getAllowedNextTerm())->toBe('Second Term');
});

test('term can get allowed next term for Second Term', function () {
    $session = AcademicSession::factory()->create();
    $term = Term::factory()->create([
        'name' => 'Second Term',
        'academic_session_id' => $session->id,
    ]);

    expect($term->getAllowedNextTerm())->toBe('Third Term');
});

test('term can get allowed next term for Third Term', function () {
    $session = AcademicSession::factory()->create();
    $term = Term::factory()->create([
        'name' => 'Third Term',
        'academic_session_id' => $session->id,
    ]);

    expect($term->getAllowedNextTerm())->toBe('First Term');
});

test('term can validate allowed migration', function () {
    $session = AcademicSession::factory()->create();
    $term = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
    ]);

    expect($term->canMigrateTo('Second Term'))->toBeTrue();
    expect($term->canMigrateTo('Third Term'))->toBeFalse();
    expect($term->canMigrateTo('First Term'))->toBeFalse();
});

test('term can check if it is last term', function () {
    $session = AcademicSession::factory()->create();
    
    $firstTerm = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
    ]);
    
    $thirdTerm = Term::factory()->create([
        'name' => 'Third Term',
        'academic_session_id' => $session->id,
    ]);

    expect($firstTerm->isLastTerm())->toBeFalse();
    expect($thirdTerm->isLastTerm())->toBeTrue();
});

test('term can get term order number', function () {
    $session = AcademicSession::factory()->create();
    
    $firstTerm = Term::factory()->create([
        'name' => 'First Term',
        'academic_session_id' => $session->id,
    ]);
    
    $secondTerm = Term::factory()->create([
        'name' => 'Second Term',
        'academic_session_id' => $session->id,
    ]);
    
    $thirdTerm = Term::factory()->create([
        'name' => 'Third Term',
        'academic_session_id' => $session->id,
    ]);

    expect($firstTerm->getTermOrder())->toBe(1);
    expect($secondTerm->getTermOrder())->toBe(2);
    expect($thirdTerm->getTermOrder())->toBe(3);
});

test('term belongs to academic session', function () {
    $session = AcademicSession::factory()->create();
    $term = Term::factory()->create([
        'academic_session_id' => $session->id,
    ]);

    expect($term->academicSession)->toBeInstanceOf(AcademicSession::class);
    expect($term->academicSession->id)->toBe($session->id);
});
