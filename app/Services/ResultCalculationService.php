<?php

namespace App\Services;

use App\Models\ClassScoreStructure;
use App\Models\MidtermSubjectResult;
use App\Models\Score;
use App\Models\Setting;
use App\Models\StudentEnrollment;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermScoringSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultCalculationService
{
    /**
     * Calculate and publish results for a single subject in a classroom.
     */
    public function calculateForSubject(int $classroomId, int $subjectId, int $sessionId, int $termId): void
    {
        $term = Term::find($termId);
        $isThirdTerm = $term && $term->order === 3;

        DB::transaction(function () use ($classroomId, $subjectId, $sessionId, $termId, $isThirdTerm) {
            if ($isThirdTerm) {
                $this->computeCumulativeSubjectResults($classroomId, $sessionId, $termId, $subjectId);
            } else {
                $this->computeSubjectResults($classroomId, $sessionId, $termId, $subjectId);
            }

            $this->computeSubjectPositionsForSubject($classroomId, $subjectId, $sessionId, $termId);
        });
    }

    /**
     * Full calculation pipeline for a single class/session/term.
     *
     * Orchestrates:  subject totals → grades → subject positions →
     *                overall totals → overall grades → overall positions.
     *
     * For Third Term (order=3), subject totals become cumulative averages
     * of all three terms: round((t1 + t2 + t3) / 3, 2).
     *
     * Everything runs inside one DB transaction for atomicity.
     */
    public function calculateForClass(int $classroomId, int $sessionId, int $termId): void
    {
        $structure = ClassScoreStructure::where('class_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        if (! $structure || ! $structure->locked) {
            throw new \RuntimeException(
                'Cannot calculate results: class score structure is not locked.'
            );
        }

        // ── Determine if this is a third-term (cumulative) calculation ────────
        $term = Term::find($termId);
        $isThirdTerm = $term && $term->order === 3;

        // ── Guard: prior terms must be calculated for third term ──────────────
        if ($isThirdTerm) {
            $termIds = $this->resolveTermIds($sessionId);

            if (! $termIds) {
                throw new \RuntimeException(
                    'Cannot calculate cumulative results: could not resolve all three term IDs for this session.'
                );
            }

            // Check that SubjectResult rows exist for terms 1 & 2
            foreach ([$termIds[1], $termIds[2]] as $priorTermId) {
                $priorExists = SubjectResult::where('classroom_id', $classroomId)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $priorTermId)
                    ->exists();

                if (! $priorExists) {
                    $priorTerm = Term::find($priorTermId);
                    throw new \RuntimeException(
                        "Cannot calculate Third Term results: {$priorTerm->name} results have not been calculated yet. Please calculate them first."
                    );
                }
            }
        }

        DB::transaction(function () use ($classroomId, $sessionId, $termId, $isThirdTerm) {
            // Step 1 + 2: Compute subject totals, grades, remarks
            if ($isThirdTerm) {
                $this->computeCumulativeSubjectResults($classroomId, $sessionId, $termId);
            } else {
                $this->computeSubjectResults($classroomId, $sessionId, $termId);
            }

            // Step 3: Subject positions (per subject, competition ranking)
            $this->computeSubjectPositions($classroomId, $sessionId, $termId);

            // Step 4: Overall totals, averages, grades
            $this->computeTermResults($classroomId, $sessionId, $termId);

            // Step 5: Overall positions (competition ranking)
            $this->computeOverallPositions($classroomId, $sessionId, $termId);
        });
    }

    /**
     * Resolve the three term IDs for a given session, keyed by order (1, 2, 3).
     *
     * @return array<int, int>|null  [1 => term_id, 2 => term_id, 3 => term_id] or null
     */
    public function resolveTermIds(int $sessionId): ?array
    {
        $terms = Term::where('session_id', $sessionId)
            ->orderBy('order')
            ->pluck('id', 'order')
            ->toArray();

        if (count($terms) !== 3 || ! isset($terms[1], $terms[2], $terms[3])) {
            return null;
        }

        return $terms;
    }

    /**
     * Resolve a numeric score to a grade + remark using tenant settings or config/grading.php.
     *
     * @return array{grade: string, remark: string, principal_comment: string}
     */
    public function resolveGrade(float $score): array
    {
        $scale = null;
        try {
            $customScaleJson = Setting::where('key', 'custom_grade_scale')->value('value');
            if ($customScaleJson) {
                $decoded = json_decode($customScaleJson, true);
                if (is_array($decoded) && ! empty($decoded)) {
                    $scale = $decoded;
                }
            }
        } catch (\Throwable $e) {
            // Fall back to config if settings table unavailable (e.g. during certain migrations/tests)
        }

        if (empty($scale)) {
            $scale = config('grading.scale', []);
        }

        if (empty($scale)) {
            return [
                'grade'             => 'F',
                'remark'            => 'Fail',
                'principal_comment' => 'A poor result. Needs urgent academic support, seriousness, and dedication.',
            ];
        }

        // Sort descending by min so highest matching threshold wins and decimal values between integer bands are captured
        usort($scale, fn ($a, $b) => $b['min'] <=> $a['min']);

        foreach ($scale as $band) {
            if ($score >= $band['min']) {
                $comment = $band['principal_comment'] ?? null;
                if (empty($comment)) {
                    $remark = strtolower($band['remark'] ?? '');
                    if (str_contains($remark, 'excellent') || str_contains($remark, 'outstanding')) {
                        $comment = 'An excellent and outstanding performance. Keep it up!';
                    } elseif (str_contains($remark, 'good') || str_contains($remark, 'credit')) {
                        $comment = 'A good effort, maintain this standard and aim higher.';
                    } elseif (str_contains($remark, 'pass') || str_contains($remark, 'fair')) {
                        $comment = 'A fair performance. More commitment and focus are required next term.';
                    } else {
                        $comment = 'A poor result. Needs urgent academic support, seriousness, and dedication.';
                    }
                }

                return [
                    'grade'             => $band['grade'],
                    'remark'            => $band['remark'] ?? '',
                    'principal_comment' => $comment,
                ];
            }
        }

        // Fallback for negative or below lowest min
        $lastBand = end($scale);
        return [
            'grade'             => $lastBand['grade'] ?? 'F',
            'remark'            => $lastBand['remark'] ?? 'Fail',
            'principal_comment' => $lastBand['principal_comment'] ?? 'A poor result. Needs urgent academic support, seriousness, and dedication.',
        ];
    }

    /**
     * Standard competition ranking (1, 2, 2, 4).
     *
     * @param  Collection  $items     Collection of associative arrays or objects
     * @param  string      $scoreKey  Key/property to rank by (descending)
     * @param  string      $rankKey   Key/property to write the rank into
     * @return Collection  The same collection with $rankKey populated
     */
    public function competitionRank(Collection $items, string $scoreKey, string $rankKey = 'position'): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $sorted = $items->sortByDesc($scoreKey)->values();

        $previousScore  = null;
        $currentRank    = 0;
        $actualPosition = 0;

        return $sorted->map(function ($item) use ($scoreKey, $rankKey, &$previousScore, &$currentRank, &$actualPosition) {
            $actualPosition++;

            $score = is_array($item) ? $item[$scoreKey] : $item->{$scoreKey};

            if ($previousScore === null || (float) $score !== (float) $previousScore) {
                $currentRank = $actualPosition;
            }

            $previousScore = $score;

            if (is_array($item)) {
                $item[$rankKey] = $currentRank;
            } else {
                $item->{$rankKey} = $currentRank;
            }

            return $item;
        });
    }

    // ─── Private pipeline steps ──────────────────────────────────────────────

    /**
     * Step 1+2: Compute and persist subject totals + grades (Term 1 & 2 — standalone).
     */
    /**
     * Step 1+2: Compute and persist subject totals + grades (Term 1 & 2 — standalone).
     */
    private function computeSubjectResults(int $classroomId, int $sessionId, int $termId, ?int $targetSubjectId = null): void
    {
        // Early dispatch: if snapshot exists with mode='midterm', run dedicated midterm pipeline
        $snapshot = TermScoringSnapshot::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        if ($snapshot && $snapshot->mode === 'midterm') {
            $this->computeMidtermSubjectResults($classroomId, $sessionId, $termId, $targetSubjectId, $snapshot);
            return;
        }

        // Get all enrolled students
        $enrolledStudentIds = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id');

        if ($enrolledStudentIds->isEmpty()) {
            return;
        }

        // Aggregate raw scores → subject totals per student
        $query = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $enrolledStudentIds);

        if ($targetSubjectId !== null) {
            $query->where('subject_id', $targetSubjectId);
        }

        $aggregates = $query->groupBy('student_id', 'subject_id')
            ->selectRaw('student_id, subject_id, SUM(score) as subject_total')
            ->get();

        foreach ($aggregates as $row) {
            $total   = round((float) $row->subject_total, 2);
            $grading = $this->resolveGrade($total);

            SubjectResult::updateOrCreate(
                [
                    'student_id' => $row->student_id,
                    'subject_id' => $row->subject_id,
                    'session_id' => $sessionId,
                    'term_id'    => $termId,
                ],
                [
                    'classroom_id' => $classroomId,
                    'total'        => $total,
                    'grade'        => $grading['grade'],
                    'remark'       => $grading['remark'],
                    'is_published' => true,
                ]
            );
        }
    }

    /**
     * Step 1+2 (Third Term): Compute cumulative subject averages across all 3 terms.
     */
    private function computeCumulativeSubjectResults(int $classroomId, int $sessionId, int $termId, ?int $targetSubjectId = null): void
    {
        $snapshot = TermScoringSnapshot::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        if ($snapshot && $snapshot->mode === 'midterm') {
            $this->computeCumulativeMidtermSubjectResults($classroomId, $sessionId, $termId, $targetSubjectId, $snapshot);
            return;
        }

        $enrolledStudentIds = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id');

        if ($enrolledStudentIds->isEmpty()) {
            return;
        }

        $termIds = $this->resolveTermIds($sessionId);

        // ── Get Term 1 & 2 subject results (including error rows) ───────────
        $priorQuery = SubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->whereIn('term_id', [$termIds[1] ?? 0, $termIds[2] ?? 0])
            ->whereIn('student_id', $enrolledStudentIds);

        if ($targetSubjectId !== null) {
            $priorQuery->where('subject_id', $targetSubjectId);
        }

        $priorResults = $priorQuery->get()
            ->groupBy(fn ($r) => $r->student_id . '-' . $r->subject_id . '-' . $r->term_id);

        // ── Get Term 3 raw score aggregates ──────────────────────────────────
        $query = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $enrolledStudentIds);

        if ($targetSubjectId !== null) {
            $query->where('subject_id', $targetSubjectId);
        }

        $term3Aggregates = $query->groupBy('student_id', 'subject_id')
            ->selectRaw('student_id, subject_id, SUM(score) as subject_total')
            ->get();

        foreach ($term3Aggregates as $row) {
            $term3Raw = round((float) $row->subject_total, 2);

            $key1 = $row->student_id . '-' . $row->subject_id . '-' . ($termIds[1] ?? 0);
            $key2 = $row->student_id . '-' . $row->subject_id . '-' . ($termIds[2] ?? 0);

            $res1 = $priorResults->get($key1)?->first();
            $res2 = $priorResults->get($key2)?->first();

            // If a prior term has errors, do NOT count it as 0 with a /3 divisor.
            // Mark the cumulative row as error and unpublished.
            if (($res1 && $res1->has_error) || ($res2 && $res2->has_error)) {
                $errTerm = ($res1 && $res1->has_error) ? 'Term 1' : 'Term 2';
                Log::warning("Cannot compute cumulative result for student {$row->student_id}, subject {$row->subject_id}: {$errTerm} has calculation errors.");
                SubjectResult::updateOrCreate(
                    [
                        'student_id' => $row->student_id,
                        'subject_id' => $row->subject_id,
                        'session_id' => $sessionId,
                        'term_id'    => $termId,
                    ],
                    [
                        'classroom_id' => $classroomId,
                        'total'        => 0.0,
                        'grade'        => '',
                        'remark'       => '',
                        'is_published' => false,
                        'has_error'    => true,
                        'error_message'=> "Cannot compute cumulative result: {$errTerm} result has errors and must be resolved first.",
                    ]
                );
                continue;
            }

            $term1Total = ($res1 && ! $res1->has_error && $res1->is_published)
                ? (float) $res1->total
                : 0.0;

            $term2Total = ($res2 && ! $res2->has_error && $res2->is_published)
                ? (float) $res2->total
                : 0.0;

            $average = round(($term1Total + $term2Total + $term3Raw) / 3, 2);
            $grading = $this->resolveGrade($average);

            SubjectResult::updateOrCreate(
                [
                    'student_id' => $row->student_id,
                    'subject_id' => $row->subject_id,
                    'session_id' => $sessionId,
                    'term_id'    => $termId,
                ],
                [
                    'classroom_id' => $classroomId,
                    'total'        => $average,
                    'grade'        => $grading['grade'],
                    'remark'       => $grading['remark'],
                    'is_published' => true,
                ]
            );
        }
    }

    /**
     * Step 3: Competition-rank students per subject.
     */
    private function computeSubjectPositions(int $classroomId, int $sessionId, int $termId): void
    {
        $subjectIds = SubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->distinct()
            ->pluck('subject_id');

        foreach ($subjectIds as $subjectId) {
            $this->computeSubjectPositionsForSubject($classroomId, $subjectId, $sessionId, $termId);
        }
    }

    private function computeSubjectPositionsForSubject(int $classroomId, int $subjectId, int $sessionId, int $termId): void
    {
        $results = SubjectResult::where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->where('is_published', true)
            ->where('has_error', false)
            ->get();

        $ranked = $this->competitionRank($results, 'total', 'position');

        foreach ($ranked as $result) {
            $result->save();
        }
    }

    /**
     * Step 4: Compute overall totals, averages, grades per student.
     */
    private function computeTermResults(int $classroomId, int $sessionId, int $termId): void
    {
        $snapshot = TermScoringSnapshot::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        $isMidterm = ($snapshot && $snapshot->mode === 'midterm');
        $decimalPlaces = $isMidterm ? (int) ($snapshot?->result_decimal_places ?? Setting::where('key', 'result_decimal_places')->value('value') ?? 2) : 2;
        $roundingRule = $isMidterm ? (string) ($snapshot?->result_rounding_rule ?? Setting::where('key', 'result_rounding_rule')->value('value') ?? 'half_up') : 'half_up';
        $midtermCalc = app(MidtermCalculationService::class);

        $studentAggregates = SubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->where('is_published', true)
            ->where('has_error', false)
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM(total) as grand_total, COUNT(*) as subjects_count')
            ->get();

        foreach ($studentAggregates as $row) {
            $subjectsCount = (int) $row->subjects_count;

            if ($isMidterm) {
                $grandTotal = (float) $midtermCalc->bcRound((string) $row->grand_total, $decimalPlaces, $roundingRule);
                $average = $subjectsCount > 0
                    ? (float) $midtermCalc->bcRound(bcdiv((string) $grandTotal, (string) $subjectsCount, 6), $decimalPlaces, $roundingRule)
                    : 0;
                $grading = $midtermCalc->resolveGradeAndRemark($average);
            } else {
                $grandTotal    = round((float) $row->grand_total, 2);
                $average       = $subjectsCount > 0 ? round($grandTotal / $subjectsCount, 2) : 0;
                $grading       = $this->resolveGrade($average);
            }

            $existing = TermResult::where('student_id', $row->student_id)
                ->where('session_id', $sessionId)
                ->where('term_id', $termId)
                ->first();

            $principalComment = ($existing && ! empty($existing->principal_comment))
                ? $existing->principal_comment
                : ($grading['principal_comment'] ?? '');

            TermResult::updateOrCreate(
                [
                    'student_id' => $row->student_id,
                    'session_id' => $sessionId,
                    'term_id'    => $termId,
                ],
                [
                    'classroom_id'      => $classroomId,
                    'subjects_count'    => $subjectsCount,
                    'grand_total'       => $grandTotal,
                    'average'           => $average,
                    'grade'             => $grading['grade'],
                    'remark'            => $grading['remark'],
                    'principal_comment' => $principalComment,
                    'is_finalized'      => true,
                ]
            );
        }
    }

    /**
     * Step 5: Competition-rank students by grand_total for overall, department, and arm positions.
     */
    private function computeOverallPositions(int $classroomId, int $sessionId, int $termId): void
    {
        $results = TermResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->get();

        if ($results->isEmpty()) {
            return;
        }

        $enrollments = StudentEnrollment::where('session_id', $sessionId)
            ->where('classroom_id', $classroomId)
            ->whereIn('student_id', $results->pluck('student_id'))
            ->get()
            ->keyBy('student_id');

        // Overall cohort ranking
        $results = $this->competitionRank($results, 'grand_total', 'overall_position');
        $classPopulation = $results->count();

        foreach ($results as $result) {
            $enrollment = $enrollments->get($result->student_id);
            $result->class_population    = $classPopulation;
            $result->_temp_department_id = $enrollment?->department_id;
            $result->_temp_class_arm_id  = $enrollment?->class_arm_id;
        }

        // Rank within department (Senior school)
        $deptGroups = $results->filter(fn ($r) => !empty($r->_temp_department_id))->groupBy('_temp_department_id');
        foreach ($deptGroups as $deptId => $deptResults) {
            $deptPopulation = $deptResults->count();
            $rankedDept = $this->competitionRank($deptResults, 'grand_total', 'department_position');
            foreach ($rankedDept as $r) {
                $r->department_population = $deptPopulation;
            }
        }

        // Rank within class arm (Junior school / streamed arms)
        $armGroups = $results->filter(fn ($r) => !empty($r->_temp_class_arm_id))->groupBy('_temp_class_arm_id');
        foreach ($armGroups as $armId => $armResults) {
            $armPopulation = $armResults->count();
            $rankedArm = $this->competitionRank($armResults, 'grand_total', 'arm_position');
            foreach ($rankedArm as $r) {
                $r->arm_population = $armPopulation;
            }
        }

        foreach ($results as $result) {
            unset($result->_temp_department_id, $result->_temp_class_arm_id);
            $result->save();
        }
    }

    /**
     * Dedicated Midterm calculation pipeline (Term 1 & Term 2).
     */
    private function computeMidtermSubjectResults(int $classroomId, int $sessionId, int $termId, ?int $targetSubjectId, TermScoringSnapshot $snapshot): void
    {
        $enrolledStudentIds = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id');

        if ($enrolledStudentIds->isEmpty()) {
            return;
        }

        $examHeadIds = collect($snapshot->exam_heads)->pluck('id')->toArray();

        $query = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $enrolledStudentIds)
            ->whereIn('score_head_id', $examHeadIds);

        if ($targetSubjectId !== null) {
            $query->where('subject_id', $targetSubjectId);
        }

        $examAggregates = $query->groupBy('student_id', 'subject_id')
            ->selectRaw('student_id, subject_id, SUM(score) as exam_total')
            ->get();

        $midtermQuery = MidtermSubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId);

        if ($targetSubjectId !== null) {
            $midtermQuery->where('subject_id', $targetSubjectId);
        }

        $midtermResults = $midtermQuery->get()->keyBy(fn ($r) => $r->student_id . '-' . $r->subject_id);

        $decimalPlaces = (int) ($snapshot->result_decimal_places ?? Setting::where('key', 'result_decimal_places')->value('value') ?? 2);
        $roundingRule = (string) ($snapshot->result_rounding_rule ?? Setting::where('key', 'result_rounding_rule')->value('value') ?? 'half_up');
        $midtermCalc = app(MidtermCalculationService::class);

        DB::transaction(function () use ($examAggregates, $midtermResults, $classroomId, $sessionId, $termId, $decimalPlaces, $roundingRule, $midtermCalc) {
            foreach ($examAggregates as $row) {
                $studentId = $row->student_id;
                $subjectId = $row->subject_id;
                $examScore = (float) $row->exam_total;
                $key = $studentId . '-' . $subjectId;
                $midterm = $midtermResults->get($key);

                $midtermStatus = $midterm?->status ?? 'missing';
                $isMissing = ($midtermStatus === 'missing');
                $isAbsent = ($midtermStatus === 'absent');
                $midtermScore = ($isMissing || $isAbsent) ? 0.0 : (float) $midterm->scaled_score;
                $rawTotal = $midtermScore + $examScore;

                if ($rawTotal > 100.0) {
                    Log::error("Total exceeded 100: student={$studentId}, subj={$subjectId}, total={$rawTotal}");
                    SubjectResult::updateOrCreate(
                        ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
                        [
                            'classroom_id'        => $classroomId,
                            'midterm_score'       => $midtermScore,
                            'exam_score'          => $examScore,
                            'midterm_status'      => $midtermStatus,
                            'has_missing_midterm' => $isMissing,
                            'has_error'           => true,
                            'error_message'       => "Total score ({$rawTotal}) exceeded 100 marks.",
                        ]
                    );
                    continue;
                }

                $total = (float) $midtermCalc->bcRound((string) $rawTotal, $decimalPlaces, $roundingRule);

                try {
                    $grading = $midtermCalc->resolveGradeAndRemark($total);
                } catch (\OutOfBoundsException $e) {
                    Log::error("Grade band missing: {$e->getMessage()}");
                    SubjectResult::updateOrCreate(
                        ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
                        [
                            'classroom_id'        => $classroomId,
                            'midterm_score'       => $midtermScore,
                            'exam_score'          => $examScore,
                            'midterm_status'      => $midtermStatus,
                            'has_missing_midterm' => $isMissing,
                            'has_error'           => true,
                            'error_message'       => $e->getMessage(),
                        ]
                    );
                    continue;
                }

                $existing = SubjectResult::where('student_id', $studentId)
                    ->where('subject_id', $subjectId)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->first();

                $payload = [
                    'classroom_id'        => $classroomId,
                    'midterm_score'       => $midtermScore,
                    'exam_score'          => $examScore,
                    'midterm_status'      => $midtermStatus,
                    'has_missing_midterm' => $isMissing,
                    'has_error'           => false,
                    'error_message'       => null,
                    'total'               => $total,
                    'grade'               => $grading['grade'],
                    'remark'              => $grading['remark'],
                    'is_published'        => true,
                ];

                SubjectResult::updateOrCreate(
                    ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
                    $payload
                );
            }
        });
    }

    /**
     * Dedicated Midterm cumulative calculation pipeline (Third Term).
     */
    private function computeCumulativeMidtermSubjectResults(int $classroomId, int $sessionId, int $termId, ?int $targetSubjectId, TermScoringSnapshot $snapshot): void
    {
        $enrolledStudentIds = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id');

        if ($enrolledStudentIds->isEmpty()) {
            return;
        }

        $termIds = $this->resolveTermIds($sessionId);

        // Get Term 1 & 2 subject results (including error rows)
        $priorQuery = SubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->whereIn('term_id', [$termIds[1] ?? 0, $termIds[2] ?? 0])
            ->whereIn('student_id', $enrolledStudentIds);

        if ($targetSubjectId !== null) {
            $priorQuery->where('subject_id', $targetSubjectId);
        }

        $priorResults = $priorQuery->get()
            ->groupBy(fn ($r) => $r->student_id . '-' . $r->subject_id . '-' . $r->term_id);

        $examHeadIds = collect($snapshot->exam_heads)->pluck('id')->toArray();

        $query = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $enrolledStudentIds)
            ->whereIn('score_head_id', $examHeadIds);

        if ($targetSubjectId !== null) {
            $query->where('subject_id', $targetSubjectId);
        }

        $term3ExamAggregates = $query->groupBy('student_id', 'subject_id')
            ->selectRaw('student_id, subject_id, SUM(score) as exam_total')
            ->get();

        $midtermQuery = MidtermSubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId);

        if ($targetSubjectId !== null) {
            $midtermQuery->where('subject_id', $targetSubjectId);
        }

        $midtermResults = $midtermQuery->get()->keyBy(fn ($r) => $r->student_id . '-' . $r->subject_id);

        $decimalPlaces = (int) ($snapshot->result_decimal_places ?? Setting::where('key', 'result_decimal_places')->value('value') ?? 2);
        $roundingRule = (string) ($snapshot->result_rounding_rule ?? Setting::where('key', 'result_rounding_rule')->value('value') ?? 'half_up');
        $midtermCalc = app(MidtermCalculationService::class);

        DB::transaction(function () use ($term3ExamAggregates, $midtermResults, $priorResults, $termIds, $classroomId, $sessionId, $termId, $decimalPlaces, $roundingRule, $midtermCalc) {
            foreach ($term3ExamAggregates as $row) {
                $studentId = $row->student_id;
                $subjectId = $row->subject_id;
                $examScore = (float) $row->exam_total;
                $key = $studentId . '-' . $subjectId;
                $midterm = $midtermResults->get($key);

                $midtermStatus = $midterm?->status ?? 'missing';
                $isMissing = ($midtermStatus === 'missing');
                $isAbsent = ($midtermStatus === 'absent');
                $midtermScore = ($isMissing || $isAbsent) ? 0.0 : (float) $midterm->scaled_score;
                $rawTotal = $midtermScore + $examScore;

                if ($rawTotal > 100.0) {
                    Log::error("Total exceeded 100 in Term 3 cumulative: student={$studentId}, subj={$subjectId}, total={$rawTotal}");
                    SubjectResult::updateOrCreate(
                        ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
                        [
                            'classroom_id'        => $classroomId,
                            'midterm_score'       => $midtermScore,
                            'exam_score'          => $examScore,
                            'midterm_status'      => $midtermStatus,
                            'has_missing_midterm' => $isMissing,
                            'has_error'           => true,
                            'error_message'       => "Total score ({$rawTotal}) exceeded 100 marks.",
                            'is_published'        => false,
                            'total'               => 0.0,
                            'grade'               => '',
                            'remark'              => '',
                        ]
                    );
                    continue;
                }

                $term3Raw = (float) $midtermCalc->bcRound((string) $rawTotal, $decimalPlaces, $roundingRule);

                $key1 = $studentId . '-' . $subjectId . '-' . ($termIds[1] ?? 0);
                $key2 = $studentId . '-' . $subjectId . '-' . ($termIds[2] ?? 0);

                $res1 = $priorResults->get($key1)?->first();
                $res2 = $priorResults->get($key2)?->first();

                // If a prior term has errors, do NOT count it as 0 with a /3 divisor.
                // Mark the cumulative row as error and unpublished.
                if (($res1 && $res1->has_error) || ($res2 && $res2->has_error)) {
                    $errTerm = ($res1 && $res1->has_error) ? 'Term 1' : 'Term 2';
                    Log::warning("Cannot compute cumulative midterm result for student {$studentId}, subject {$subjectId}: {$errTerm} has calculation errors.");
                    SubjectResult::updateOrCreate(
                        ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
                        [
                            'classroom_id'        => $classroomId,
                            'midterm_score'       => $midtermScore,
                            'exam_score'          => $examScore,
                            'midterm_status'      => $midtermStatus,
                            'has_missing_midterm' => $isMissing,
                            'has_error'           => true,
                            'error_message'       => "Cannot compute cumulative result: {$errTerm} result has errors and must be resolved first.",
                            'is_published'        => false,
                            'total'               => 0.0,
                            'grade'               => '',
                            'remark'              => '',
                        ]
                    );
                    continue;
                }

                $term1Total = ($res1 && ! $res1->has_error && $res1->is_published)
                    ? (float) $res1->total
                    : 0.0;

                $term2Total = ($res2 && ! $res2->has_error && $res2->is_published)
                    ? (float) $res2->total
                    : 0.0;

                $sum3Terms = bcadd(bcadd((string) $term1Total, (string) $term2Total, 6), (string) $term3Raw, 6);
                $averageUnrounded = bcdiv($sum3Terms, '3', 6);
                $average = (float) $midtermCalc->bcRound($averageUnrounded, $decimalPlaces, $roundingRule);

                try {
                    $grading = $midtermCalc->resolveGradeAndRemark($average);
                } catch (\OutOfBoundsException $e) {
                    Log::error("Grade band missing: {$e->getMessage()}");
                    SubjectResult::updateOrCreate(
                        ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
                        [
                            'classroom_id'        => $classroomId,
                            'midterm_score'       => $midtermScore,
                            'exam_score'          => $examScore,
                            'midterm_status'      => $midtermStatus,
                            'has_missing_midterm' => $isMissing,
                            'has_error'           => true,
                            'error_message'       => $e->getMessage(),
                        ]
                    );
                    continue;
                }

                $existing = SubjectResult::where('student_id', $studentId)
                    ->where('subject_id', $subjectId)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->first();

                $payload = [
                    'classroom_id'        => $classroomId,
                    'midterm_score'       => $midtermScore,
                    'exam_score'          => $examScore,
                    'midterm_status'      => $midtermStatus,
                    'has_missing_midterm' => $isMissing,
                    'has_error'           => false,
                    'error_message'       => null,
                    'total'               => $average,
                    'grade'               => $grading['grade'],
                    'remark'              => $grading['remark'],
                    'is_published'        => true,
                ];

                SubjectResult::updateOrCreate(
                    ['student_id' => $studentId, 'subject_id' => $subjectId, 'session_id' => $sessionId, 'term_id' => $termId],
                    $payload
                );
            }
        });
    }
}
