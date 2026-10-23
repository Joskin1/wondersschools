<?php

use App\Models\MidtermSubjectResult;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\TermScoringSnapshot;
use App\Services\MidtermCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new MidtermCalculationService();
});

it('calculates scaleToWeight with bcmath multiply-first precision', function () {
    // 1.5 / 20 * 11 = 16.5 / 20 = 0.825 -> 0.83
    $rawUnrounded1 = $this->service->scaleToWeight('1.5', '20', 11, 6);
    expect($this->service->bcRound($rawUnrounded1, 2, 'half_up'))->toBe('0.83');

    // 5.5 / 20 * 11 = 60.5 / 20 = 3.025 -> 3.03
    $rawUnrounded2 = $this->service->scaleToWeight('5.5', '20', 11, 6);
    expect($this->service->bcRound($rawUnrounded2, 2, 'half_up'))->toBe('3.03');
});

it('handles floor and ceil across scales 0, 1, and 2 with zero floating point bugs', function () {
    // Scale 0
    expect($this->service->bcRound('3.5', 0, 'half_up'))->toBe('4')
        ->and($this->service->bcRound('3.4', 0, 'half_up'))->toBe('3')
        ->and($this->service->bcRound('3.1', 0, 'ceil'))->toBe('4')
        ->and($this->service->bcRound('3.0', 0, 'ceil'))->toBe('3')
        ->and($this->service->bcRound('3.9', 0, 'floor'))->toBe('3');

    // Scale 1
    expect($this->service->bcRound('3.55', 1, 'half_up'))->toBe('3.6')
        ->and($this->service->bcRound('3.54', 1, 'half_up'))->toBe('3.5')
        ->and($this->service->bcRound('3.51', 1, 'ceil'))->toBe('3.6')
        ->and($this->service->bcRound('3.59', 1, 'floor'))->toBe('3.5');

    // Scale 2
    expect($this->service->bcRound('0.29', 2, 'floor'))->toBe('0.29')
        ->and($this->service->bcRound('0.291', 2, 'floor'))->toBe('0.29')
        ->and($this->service->bcRound('0.299', 2, 'floor'))->toBe('0.29')
        ->and($this->service->bcRound('1.10', 2, 'ceil'))->toBe('1.10')
        ->and($this->service->bcRound('1.101', 2, 'ceil'))->toBe('1.11');
});

it('resolves grade and remark from config fallback and fails loudly on out of bounds', function () {
    $res85 = $this->service->resolveGradeAndRemark(85.0);
    expect($res85['grade'])->toBe('A')
        ->and($res85['remark'])->toBe('Excellent');

    $res65 = $this->service->resolveGradeAndRemark(65.0);
    expect($res65['grade'])->toBe('B')
        ->and($res65['remark'])->toBe('Very Good');

    $res20 = $this->service->resolveGradeAndRemark(20.0);
    expect($res20['grade'])->toBe('F')
        ->and($res20['remark'])->toBe('Fail');

    // Grade gap test: 69.5 at default integer bands [60-69], [70-100] must resolve to Grade B
    $res69_5 = $this->service->resolveGradeAndRemark(69.5);
    expect($res69_5['grade'])->toBe('B')
        ->and($res69_5['remark'])->toBe('Very Good');

    // Out of bounds: -5.0 or 105.0 must throw \OutOfBoundsException
    expect(fn () => $this->service->resolveGradeAndRemark(-5.0))->toThrow(\OutOfBoundsException::class);
    expect(fn () => $this->service->resolveGradeAndRemark(105.0))->toThrow(\OutOfBoundsException::class);
});

it('rejects unknown rounding modes in bcRound', function () {
    expect(fn () => $this->service->bcRound('3.45', 2, 'invalid_mode'))
        ->toThrow(\InvalidArgumentException::class, 'Unsupported rounding mode: invalid_mode');
});

it('validates grading scale band contiguity and catches gaps or overlaps', function () {
    $validBands = [
        ['min' => 0.00,  'max' => 39.99, 'grade' => 'F'],
        ['min' => 40.00, 'max' => 49.99, 'grade' => 'D'],
        ['min' => 50.00, 'max' => 69.99, 'grade' => 'C'],
        ['min' => 70.00, 'max' => 100.0, 'grade' => 'A'],
    ];

    expect(fn () => $this->service->validateBandsContiguity($validBands, 2))->not->toThrow(\Exception::class);

    // Overlap: Band D starts at 39.00 while Band F ends at 39.99
    $overlapBands = [
        ['min' => 0.00,  'max' => 39.99, 'grade' => 'F'],
        ['min' => 39.00, 'max' => 49.99, 'grade' => 'D'],
    ];
    expect(fn () => $this->service->validateBandsContiguity($overlapBands, 2))->toThrow(\InvalidArgumentException::class);

    // Gap: Band D starts at 45.00 while Band F ends at 39.99 (gap 40-44.99)
    $gapBands = [
        ['min' => 0.00,  'max' => 39.99, 'grade' => 'F'],
        ['min' => 45.00, 'max' => 100.0, 'grade' => 'A'],
    ];
    expect(fn () => $this->service->validateBandsContiguity($gapBands, 2))->toThrow(\InvalidArgumentException::class);
});

it('resolves remark scale from custom settings and fallback', function () {
    // Custom remark scale in settings
    \App\Models\Setting::updateOrCreate(
        ['key' => 'custom_remark_scale'],
        ['value' => json_encode([
            ['min' => 70, 'max' => 100, 'remark' => 'Distinction'],
            ['min' => 60, 'max' => 69,  'remark' => 'Credit'],
            ['min' => 0,  'max' => 59,  'remark' => 'Pass'],
        ])]
    );

    $this->service->resetCachedScales();

    $res75 = $this->service->resolveGradeAndRemark(75.0);
    expect($res75['remark'])->toBe('Distinction');

    $res63 = $this->service->resolveGradeAndRemark(63.0);
    expect($res63['remark'])->toBe('Credit');

    $res45 = $this->service->resolveGradeAndRemark(45.0);
    expect($res45['remark'])->toBe('Pass');

    // Clean up
    \App\Models\Setting::where('key', 'custom_remark_scale')->delete();
    $this->service->resetCachedScales();
});

it('resolves boundary gap values 59.5, 49.5, 44.5, 39.5 at 0, 1, and 2 decimal places', function () {
    // 2 Decimals
    $r59_50 = $this->service->resolveGradeAndRemark(59.50);
    expect($r59_50['grade'])->toBe('C')->and($r59_50['remark'])->toBe('Good');

    $r49_50 = $this->service->resolveGradeAndRemark(49.50);
    expect($r49_50['grade'])->toBe('D')->and($r49_50['remark'])->toBe('Fair');

    $r44_50 = $this->service->resolveGradeAndRemark(44.50);
    expect($r44_50['grade'])->toBe('E')->and($r44_50['remark'])->toBe('Pass');

    $r39_50 = $this->service->resolveGradeAndRemark(39.50);
    expect($r39_50['grade'])->toBe('F')->and($r39_50['remark'])->toBe('Fail');

    // 1 Decimal
    $r59_5 = $this->service->resolveGradeAndRemark(59.5);
    expect($r59_5['grade'])->toBe('C')->and($r59_5['remark'])->toBe('Good');

    $r49_5 = $this->service->resolveGradeAndRemark(49.5);
    expect($r49_5['grade'])->toBe('D')->and($r49_5['remark'])->toBe('Fair');

    $r44_5 = $this->service->resolveGradeAndRemark(44.5);
    expect($r44_5['grade'])->toBe('E')->and($r44_5['remark'])->toBe('Pass');

    $r39_5 = $this->service->resolveGradeAndRemark(39.5);
    expect($r39_5['grade'])->toBe('F')->and($r39_5['remark'])->toBe('Fail');

    // 0 Decimals (integers at the lower edge of default bands: 59, 49, 44, 39)
    $r59 = $this->service->resolveGradeAndRemark(59.0);
    expect($r59['grade'])->toBe('C')->and($r59['remark'])->toBe('Good');

    $r49 = $this->service->resolveGradeAndRemark(49.0);
    expect($r49['grade'])->toBe('D')->and($r49['remark'])->toBe('Fair');

    $r44 = $this->service->resolveGradeAndRemark(44.0);
    expect($r44['grade'])->toBe('E')->and($r44['remark'])->toBe('Pass');

    $r39 = $this->service->resolveGradeAndRemark(39.0);
    expect($r39['grade'])->toBe('F')->and($r39['remark'])->toBe('Fail');
});
