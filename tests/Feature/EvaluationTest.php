<?php

use App\Models\AcademicSession;
use App\Models\EvaluationSetting;
use App\Services\EvaluationService;
use Illuminate\Validation\ValidationException;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses()->group('feature', 'evaluation');

test('validates CA and Exam scores against max limits', function () {
    $session = AcademicSession::factory()->create();
    EvaluationSetting::create([
        'academic_session_id' => $session->id,
        'name' => 'CA',
        'max_score' => 40,
    ]);
    EvaluationSetting::create([
        'academic_session_id' => $session->id,
        'name' => 'Exam',
        'max_score' => 60,
    ]);

    $service = new EvaluationService();

    // Valid scores
    expect(fn() => $service->validateScores($session->id, 35, 55))
        ->not->toThrow(ValidationException::class);

    // Invalid CA score
    expect(fn() => $service->validateScores($session->id, 45, 55))
        ->toThrow(ValidationException::class);

    // Invalid Exam score
    expect(fn() => $service->validateScores($session->id, 35, 65))
        ->toThrow(ValidationException::class);
});

test('validates session total equals 100', function () {
    $session = AcademicSession::factory()->create();
    
    // Valid: CA (40) + Exam (60) = 100
    EvaluationSetting::create([
        'academic_session_id' => $session->id,
        'name' => 'CA',
        'max_score' => 40,
    ]);
    EvaluationSetting::create([
        'academic_session_id' => $session->id,
        'name' => 'Exam',
        'max_score' => 60,
    ]);

    $service = new EvaluationService();
    
    expect(fn() => $service->validateSessionTotal($session->id))
        ->not->toThrow(ValidationException::class);
});

test('throws exception when session total does not equal 100', function () {
    $session = AcademicSession::factory()->create();
    
    // Invalid: CA (30) + Exam (60) = 90
    EvaluationSetting::create([
        'academic_session_id' => $session->id,
        'name' => 'CA',
        'max_score' => 30,
    ]);
    EvaluationSetting::create([
        'academic_session_id' => $session->id,
        'name' => 'Exam',
        'max_score' => 60,
    ]);

    $service = new EvaluationService();
    
    expect(fn() => $service->validateSessionTotal($session->id))
        ->toThrow(ValidationException::class);
});

test('throws exception when evaluation settings are missing', function () {
    $session = AcademicSession::factory()->create();
    $service = new EvaluationService();

    expect(fn() => $service->validateScores($session->id, 35, 55))
        ->toThrow(ValidationException::class);
});
