<?php

namespace App\Services;

use App\Models\ClassMidtermConfig;
use App\Models\ClassScoreStructure;
use App\Models\MidtermSubjectResult;
use App\Models\MidtermTermResult;
use App\Models\Score;
use App\Models\Setting;
use App\Models\StudentEnrollment;
use App\Models\TermScoringSnapshot;

class MidtermCalculationService
{
    private ?array $cachedGradeScale = null;
    private ?array $cachedRemarkScale = null;

    /**
     * Exact bcmath rounding implementation avoiding binary floating point errors.
     * Handles 'half_up', 'floor', and 'ceil' with string precision for any scale (0, 1, 2, ...).
     */
    public function bcRound(string $val, int $scale = 2, string $mode = 'half_up'): string
    {
        if (! in_array($mode, ['half_up', 'floor', 'ceil'], true)) {
            throw new \InvalidArgumentException("Unsupported rounding mode: {$mode}. Supported modes: half_up, floor, ceil.");
        }

        if (! str_contains($val, '.')) {
            return $scale > 0 ? $val . '.' . str_repeat('0', $scale) : $val;
        }

        [$intPart, $fracPart] = explode('.', $val, 2);
        if (strlen($fracPart) <= $scale) {
            return $intPart . ($scale > 0 ? '.' . str_pad($fracPart, $scale, '0') : '');
        }

        $targetFrac = $scale > 0 ? substr($fracPart, 0, $scale) : '';
        $nextDigit = (int) $fracPart[$scale];
        $remaining = substr($fracPart, $scale + 1);
        $hasMoreNonZero = (bool) preg_match('/[1-9]/', $remaining);

        if ($mode === 'floor') {
            return $intPart . ($scale > 0 ? '.' . $targetFrac : '');
        }

        if ($mode === 'ceil') {
            if ($nextDigit > 0 || $hasMoreNonZero) {
                $add = $scale > 0 ? ('0.' . str_repeat('0', $scale - 1) . '1') : '1';
                $truncated = $intPart . ($scale > 0 ? '.' . $targetFrac : '');
                return bcadd($truncated, $add, $scale);
            }
            return $intPart . ($scale > 0 ? '.' . $targetFrac : '');
        }

        if ($mode === 'half_up') {
            if ($nextDigit >= 5) {
                $add = $scale > 0 ? ('0.' . str_repeat('0', $scale - 1) . '1') : '1';
                $truncated = $intPart . ($scale > 0 ? '.' . $targetFrac : '');
                return bcadd($truncated, $add, $scale);
            }

            return $intPart . ($scale > 0 ? '.' . $targetFrac : '');
        }

        throw new \InvalidArgumentException("Unsupported rounding mode: {$mode}. Supported modes: half_up, floor, ceil.");
    }

    /**
     * Exact formula: (rawSum * weight) / rawMax (multiply first, divide last).
     */
    public function scaleToWeight(string $rawSum, string $rawMax, int $midtermWeight, int $scale = 6): string
    {
        if (bccomp($rawMax, '0', 6) <= 0) {
            return '0';
        }

        $product = bcmul($rawSum, (string) $midtermWeight, $scale);
        return bcdiv($product, $rawMax, $scale);
    }

    /**
     * Compute single student-subject midterm row with 3 distinct states:
     * 1. 'missing': No scores entered or any required head unscored and not marked absent.
     * 2. 'absent': All heads marked is_absent (or raw max is 0).
     * 3. 'complete': Calculated scaled score.
     */
    public function computeStudentSubjectMidterm(
        int $studentId,
        int $subjectId,
        int $classroomId,
        int $sessionId,
        int $termId,
        TermScoringSnapshot $snapshot,
        string $absentPolicy = 'count_as_zero',
        int $decimalPlaces = 2,
    ): MidtermSubjectResult {
        $absentPolicy = $snapshot->absent_policy ?: $absentPolicy;
        $decimalPlaces = $snapshot->result_decimal_places !== null ? (int) $snapshot->result_decimal_places : $decimalPlaces;
        $roundingRule = $snapshot->result_rounding_rule ?: $roundingRule;

        $midtermHeadIds = collect($snapshot->midterm_heads)->pluck('id')->toArray();
        $scores = Score::where('classroom_id', $classroomId)
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('score_head_id', $midtermHeadIds)
            ->get()
            ->keyBy('score_head_id');

        $rawSum = '0';
        $studentRawMax = 0;
        $hasUnscoredHead = false;
        $allAbsent = true;

        foreach ($snapshot->midterm_heads as $head) {
            $effectiveMax = (int) ($head['effective_max'] ?? $head['max_score']);
            $scoreRecord = $scores->get($head['id']);

            if ($scoreRecord === null) {
                $hasUnscoredHead = true;
                continue;
            }

            $isAbsent = (bool) $scoreRecord->is_absent;
            $scoreVal = (string) ($scoreRecord->score ?? '0');

            if (! $isAbsent) {
                $allAbsent = false;
            }

            if ($isAbsent && $absentPolicy === 'excluded') {
                continue; // Exclude head max from student's raw max
            }

            $studentRawMax += $effectiveMax;
            $rawSum = bcadd($rawSum, $scoreVal, 4);
        }

        // 1. Missing: any head without a score row and not marked absent
        if ($scores->isEmpty() || $hasUnscoredHead) {
            return MidtermSubjectResult::updateOrCreate(
                ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
                [
                    'classroom_id'    => $classroomId,
                    'raw_score'       => 0,
                    'student_raw_max' => $studentRawMax,
                    'midterm_weight'  => $snapshot->midterm_weight,
                    'scaled_score'    => null,
                    'percentage'      => null,
                    'grade'           => '—',
                    'remark'          => 'Incomplete',
                    'status'          => 'missing',
                ]
            );
        }

        // 2. Absent: all heads marked is_absent
        if ($allAbsent || $studentRawMax === 0) {
            return MidtermSubjectResult::updateOrCreate(
                ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
                [
                    'classroom_id'    => $classroomId,
                    'raw_score'       => 0,
                    'student_raw_max' => 0,
                    'midterm_weight'  => $snapshot->midterm_weight,
                    'scaled_score'    => null,
                    'percentage'      => null,
                    'grade'           => 'ABS',
                    'remark'          => 'No Midterm',
                    'status'          => 'absent',
                ]
            );
        }

        // 3. Complete: calculate scaled score
        $scaledUnrounded = $this->scaleToWeight($rawSum, (string) $studentRawMax, $snapshot->midterm_weight, 6);
        $scaledScore = (float) $this->bcRound($scaledUnrounded, $decimalPlaces, $roundingRule);

        $percentUnrounded = bcdiv(bcmul((string) $scaledScore, '100', 6), (string) $snapshot->midterm_weight, 6);
        $percentage = (float) $this->bcRound($percentUnrounded, $decimalPlaces, $roundingRule);

        $gradeInfo = $this->resolveGradeAndRemark($percentage);

        return MidtermSubjectResult::updateOrCreate(
            ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
            [
                'classroom_id'    => $classroomId,
                'raw_score'       => (float) $rawSum,
                'student_raw_max' => $studentRawMax,
                'midterm_weight'  => $snapshot->midterm_weight,
                'scaled_score'    => $scaledScore,
                'percentage'      => $percentage,
                'grade'           => $gradeInfo['grade'],
                'remark'          => $gradeInfo['remark'],
                'status'          => 'complete',
            ]
        );
    }

    /**
     * Compute and save summary midterm results per student in classroom.
     * Preserves existing teacher_comment and head_teacher_comment during recalculation.
     * Rule: ABS and missing subjects stay in total obtainable and contribute 0 to total obtained.
     */
    public function computeClassMidtermSummary(
        int $classroomId,
        int $sessionId,
        int $termId,
        TermScoringSnapshot $snapshot,
        int $decimalPlaces = 2,
        string $roundingRule = 'half_up'
    ): void {
        $enrolledStudentIds = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id');

        if ($enrolledStudentIds->isEmpty()) {
            return;
        }

        $allMidtermResults = MidtermSubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $enrolledStudentIds)
            ->get()
            ->groupBy('student_id');

        $decimalPlaces = $snapshot->result_decimal_places !== null ? (int) $snapshot->result_decimal_places : $decimalPlaces;
        $roundingRule = $snapshot->result_rounding_rule ?: $roundingRule;

        foreach ($enrolledStudentIds as $studentId) {
            $studentRows = $allMidtermResults->get($studentId, collect());
            // Total obtainable computed strictly from the subjects this student actually offers
            $subjectsCount = $studentRows->count();

            $totalObtainable = (float) ($snapshot->midterm_weight * $subjectsCount);
            $totalObtained = (float) $studentRows->where('status', 'complete')->sum(fn ($r) => (float) $r->scaled_score);

            $percentage = $totalObtainable > 0
                ? (float) $this->bcRound(bcdiv(bcmul((string) $totalObtained, '100', 6), (string) $totalObtainable, 6), $decimalPlaces, $roundingRule)
                : 0.0;

            $gradeInfo = $this->resolveGradeAndRemark($percentage);

            // Item 8: Preserve comments by omitting comment keys from updateOrCreate (never overwrites existing comments)
            MidtermTermResult::updateOrCreate(
                ['student_id' => $studentId, 'session_id' => $sessionId, 'term_id' => $termId],
                [
                    'classroom_id'     => $classroomId,
                    'subjects_count'   => $subjectsCount,
                    'total_obtainable' => $totalObtainable,
                    'total_obtained'   => $totalObtained,
                    'percentage'       => $percentage,
                    'grade'            => $gradeInfo['grade'],
                    'remark'           => $gradeInfo['remark'],
                ]
            );
        }
    }

    /**
     * Resolve Grade and Remark using cached tenant settings with fallback to config('grading.scale').
     * Throws \OutOfBoundsException if score cannot be matched to any band (no silent 'F'/'Fail').
     */
    public function resolveGradeAndRemark(float $score): array
    {
        if ($this->cachedGradeScale === null) {
            $this->loadScales();
        }

        $highestMax = (float) max(array_column($this->cachedGradeScale, 'max'));
        $lowestMin = (float) min(array_column($this->cachedGradeScale, 'min'));

        if ($score < $lowestMin || $score > $highestMax) {
            throw new \OutOfBoundsException("No grading band found for score: {$score}. Check grading configuration.");
        }

        // Sort descending by min to guarantee highest matching band wins and bridge integer gaps
        $sortedGrades = $this->cachedGradeScale;
        usort($sortedGrades, fn ($a, $b) => $b['min'] <=> $a['min']);

        $grade = null;
        foreach ($sortedGrades as $band) {
            if ($score >= $band['min']) {
                $grade = $band['grade'];
                break;
            }
        }

        $sortedRemarks = $this->cachedRemarkScale;
        usort($sortedRemarks, fn ($a, $b) => $b['min'] <=> $a['min']);

        $remark = null;
        foreach ($sortedRemarks as $band) {
            if ($score >= $band['min']) {
                $remark = $band['remark'];
                break;
            }
        }

        if ($grade === null || $remark === null) {
            throw new \OutOfBoundsException("No grading band found for score: {$score}. Check grading configuration.");
        }

        return [
            'grade'  => $grade,
            'remark' => $remark,
        ];
    }

    /**
     * Clear cached scales (used during tests or settings update).
     */
    public function resetCachedScales(): void
    {
        $this->cachedGradeScale = null;
        $this->cachedRemarkScale = null;
    }

    private function loadScales(): void
    {
        $customGradeScale = Setting::where('key', 'custom_grade_scale')->value('value');
        $customRemarkScale = Setting::where('key', 'custom_remark_scale')->value('value');

        $grades = $customGradeScale ? json_decode($customGradeScale, true) : null;
        $remarks = $customRemarkScale ? json_decode($customRemarkScale, true) : null;

        if ((empty($remarks) || ! is_array($remarks)) && ! empty($grades) && is_array($grades) && isset($grades[0]['remark'])) {
            $remarks = array_map(fn ($b) => ['min' => $b['min'], 'max' => $b['max'], 'remark' => $b['remark']], $grades);
        }

        $fallbackScale = config('grading.scale', []);

        $this->cachedGradeScale = (! empty($grades) && is_array($grades))
            ? $grades
            : array_map(fn ($b) => ['min' => $b['min'], 'max' => $b['max'], 'grade' => $b['grade']], $fallbackScale);

        $this->cachedRemarkScale = (! empty($remarks) && is_array($remarks))
            ? $remarks
            : array_map(fn ($b) => ['min' => $b['min'], 'max' => $b['max'], 'remark' => $b['remark']], $fallbackScale);
    }

    /**
     * Validate that grading scale bands are contiguous with no gaps or overlaps.
     */
    public function validateBandsContiguity(array $bands, int $decimals = 2): void
    {
        if (empty($bands)) {
            throw new \InvalidArgumentException('Grading scale bands cannot be empty.');
        }

        $step = 1 / (10 ** $decimals);

        // Sort by min ascending
        usort($bands, fn ($a, $b) => $a['min'] <=> $b['min']);

        for ($i = 0; $i < count($bands) - 1; $i++) {
            $currentMax = (float) $bands[$i]['max'];
            $nextMin = (float) $bands[$i + 1]['min'];
            $labelCurrent = $bands[$i]['grade'] ?? $bands[$i]['remark'] ?? ('Band ' . ($i + 1));
            $labelNext = $bands[$i + 1]['grade'] ?? $bands[$i + 1]['remark'] ?? ('Band ' . ($i + 2));

            if ($nextMin <= $currentMax) {
                throw new \InvalidArgumentException("Overlapping bands detected: Band {$labelCurrent} max ({$currentMax}) overlaps with Band {$labelNext} min ({$nextMin}).");
            }

            $isIntegerStep = ((float) $currentMax == (int) $currentMax && (float) $nextMin == (int) $nextMin && (int) $nextMin === (int) $currentMax + 1);
            $expectedNextMin = round($currentMax + $step, $decimals);

            if (! $isIntegerStep && abs($nextMin - $expectedNextMin) > 0.0001) {
                throw new \InvalidArgumentException("Gap detected between bands: Band {$labelCurrent} ends at {$currentMax}, but Band {$labelNext} starts at {$nextMin}. Expected {$expectedNextMin}.");
            }
        }
    }

    /**
     * Get or create a frozen term scoring snapshot for a class and term.
     * Created automatically on the first score saved on either EnterMidtermScores or EnterScores.
     */
    public function getOrCreateSnapshot(int $classroomId, int $sessionId, int $termId, ?int $userId = null): TermScoringSnapshot
    {
        $existing = TermScoringSnapshot::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $useMidterm = filter_var(Setting::where('key', 'use_midterm_results')->value('value'), FILTER_VALIDATE_BOOLEAN);
        $midtermConfig = $useMidterm
            ? ClassMidtermConfig::with('heads.scoreHead')->where('classroom_id', $classroomId)->first()
            : null;

        // Legacy mode: unconfigured class or toggle is OFF
        if (! $useMidterm || ! $midtermConfig) {
            $examStructure = ClassScoreStructure::with('items.scoreHead')
                ->where('class_id', $classroomId)
                ->where('session_id', $sessionId)
                ->where('term_id', $termId)
                ->first();

            if (! $examStructure || $examStructure->items->isEmpty()) {
                throw new \DomainException('Exam score structure for this class has not been configured. Admins must set up the score structure before scores can be entered.');
            }

            $examHeads = $examStructure->items->map(fn ($item) => [
                'id'            => $item->score_head_id,
                'name'          => $item->scoreHead->name,
                'effective_max' => (int) ($item->max_score_override ?? $item->scoreHead->max_score),
            ])->values()->toArray();

            $examSum = (int) collect($examHeads)->sum('effective_max');
            if ($examSum !== 100) {
                throw new \DomainException("Exam score structure total ({$examSum}) must equal exactly 100 marks.");
            }

            $examStructure->update(['locked' => true]);

            $absentPolicy = (string) (Setting::where('key', 'midterm_absent_policy')->value('value') ?? 'count_as_zero');
            $decimalPlaces = (int) (Setting::where('key', 'result_decimal_places')->value('value') ?? 2);
            $roundingRule = (string) (Setting::where('key', 'result_rounding_rule')->value('value') ?? 'half_up');

            return TermScoringSnapshot::create([
                'classroom_id'          => $classroomId,
                'session_id'            => $sessionId,
                'term_id'               => $termId,
                'mode'                  => 'legacy',
                'midterm_weight'        => 0,
                'raw_max'               => 0,
                'calculation_method'    => 'none',
                'absent_policy'         => $absentPolicy,
                'result_decimal_places' => $decimalPlaces,
                'result_rounding_rule'  => $roundingRule,
                'midterm_heads'         => [],
                'exam_heads'            => $examHeads,
                'snapshotted_by'        => $userId,
                'snapshotted_at'        => now(),
            ]);
        }

        // Midterm mode
        if ($midtermConfig->heads->isEmpty()) {
            throw new \DomainException('Mid-term score heads have not been assigned to this class structure.');
        }

        $examStructure = ClassScoreStructure::with('items.scoreHead')
            ->where('class_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        if (! $examStructure || $examStructure->items->isEmpty()) {
            throw new \DomainException('Exam score structure for this class and term has not been configured. Admins must set up the exam structure before scores can be entered.');
        }

        $midtermHeads = $midtermConfig->heads
            ->filter(fn ($h) => $h->scoreHead !== null)
            ->map(fn ($h) => [
                'id'            => $h->score_head_id,
                'name'          => $h->scoreHead->name,
                'effective_max' => (int) ($h->max_score_override ?? $h->scoreHead->max_score),
            ])->values()->toArray();

        $rawMax = (int) collect($midtermHeads)->sum('effective_max');

        $examHeads = $examStructure->items
            ->filter(fn ($i) => $i->scoreHead !== null)
            ->map(fn ($item) => [
                'id'            => $item->score_head_id,
                'name'          => $item->scoreHead->name,
                'effective_max' => (int) ($item->max_score_override ?? $item->scoreHead->max_score),
            ])->values()->toArray();

        $examSum = (int) collect($examHeads)->sum('effective_max');
        $expectedExamSum = 100 - (int) $midtermConfig->midterm_weight;

        if ($examSum !== $expectedExamSum) {
            throw new \DomainException("Exam score structure total ({$examSum}) does not match expected total ({$expectedExamSum} = 100 - {$midtermConfig->midterm_weight}).");
        }

        $examStructure->update(['locked' => true]);

        $absentPolicy = (string) (Setting::where('key', 'midterm_absent_policy')->value('value') ?? 'count_as_zero');
        $decimalPlaces = (int) (Setting::where('key', 'result_decimal_places')->value('value') ?? 2);
        $roundingRule = (string) (Setting::where('key', 'result_rounding_rule')->value('value') ?? 'half_up');

        return TermScoringSnapshot::create([
            'classroom_id'          => $classroomId,
            'session_id'            => $sessionId,
            'term_id'               => $termId,
            'mode'                  => 'midterm',
            'midterm_weight'        => (int) $midtermConfig->midterm_weight,
            'raw_max'               => $rawMax,
            'calculation_method'    => 'scale_to_weight',
            'absent_policy'         => $absentPolicy,
            'result_decimal_places' => $decimalPlaces,
            'result_rounding_rule'  => $roundingRule,
            'midterm_heads'         => $midtermHeads,
            'exam_heads'            => $examHeads,
            'snapshotted_by'        => $userId,
            'snapshotted_at'        => now(),
        ]);
    }
}
