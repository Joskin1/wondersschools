<?php

use App\Models\AcademicSession;
use App\Models\SystemSetting;
use App\Models\Term;
use App\Services\MigrationService;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses()->group('feature', 'migration');

test('migration validates sequence', function () {
    $session = AcademicSession::factory()->create(['name' => '2030/2031']);
    $term1 = Term::factory()->create(['name' => 'First Term']);
    $term2 = Term::factory()->create(['name' => 'Second Term']);
    $term3 = Term::factory()->create(['name' => 'Third Term']);

    SystemSetting::create(['key' => 'current_session_id', 'value' => $session->id]);
    SystemSetting::create(['key' => 'current_term_id', 'value' => $term1->id]);

    $service = new MigrationService();

    // Valid: First -> Second
    $service->migrateTerm($term2->id);
    expect(SystemSetting::where('key', 'current_term_id')->value('value'))
        ->toBe($term2->id);

    // Invalid: Second -> First
    expect(fn() => $service->migrateTerm($term1->id))
        ->toThrow(Exception::class);
});

test('migration increments session on third to first', function () {
    $session = AcademicSession::factory()->create(['name' => '2031/2032']);
    $term1 = Term::factory()->create(['name' => 'First Term']);
    $term3 = Term::factory()->create(['name' => 'Third Term']);

    SystemSetting::create(['key' => 'current_session_id', 'value' => $session->id]);
    SystemSetting::create(['key' => 'current_term_id', 'value' => $term3->id]);

    $service = new MigrationService();

    $service->migrateTerm($term1->id);

    $newSessionId = SystemSetting::where('key', 'current_session_id')->value('value');
    $newSession = AcademicSession::find($newSessionId);

    expect($newSession->name)->toBe('2032/2033');
    expect(SystemSetting::where('key', 'current_term_id')->value('value'))
        ->toBe($term1->id);
});
