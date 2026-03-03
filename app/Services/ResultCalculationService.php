<?php

namespace App\Services;

use App\Models\ClassScoreStructure;
use App\Models\Score;
use App\Models\StudentEnrollment;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResultCalculationService
{
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
     *
     * @throws \RuntimeException if the score structure is not locked.
     * @throws \RuntimeException if prior term results are missing (term 3 only).
     */
    public function calculateForClass(int $classroomId, int $sessionId, int $termId): void
    {
        // ── Guard: structure must be locked ───────────────────────────────────
        $structure = ClassScoreStructure::where('class_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        if (! $structure || ! $structure->locked) {
            throw new \RuntimeException(
                'Cannot calculate results: the score structure for this class/term is not locked.'
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
     * Resolve a numeric score to a grade + remark using config/grading.php.
     *
     * @return array{grade: string, remark: string}
     */
    public function resolveGrade(float $score): array
    {
        $scale = config('grading.scale', []);

        foreach ($scale as $band) {
            if ($score >= $band['min'] && $score <= $band['max']) {
                return ['grade' => $band['grade'], 'remark' => $band['remark']];
            }
        }

        // Fallback — should never happen if config covers 0–100
        return ['grade' => 'F', 'remark' => 'Fail'];
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

            if ((float) $score !== (float) $previousScore) {
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
    private function computeSubjectResults(int $classroomId, int $sessionId, int $termId): void
    {
        // Get all enrolled students
        $enrolledStudentIds = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id');

        if ($enrolledStudentIds->isEmpty()) {
            return;
        }

        // Aggregate raw scores → subject totals per student
        $aggregates = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $enrolledStudentIds)
            ->groupBy('student_id', 'subject_id')
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
                ]
            );
        }
    }

    /**
     * Step 1+2 (Third Term): Compute cumulative subject averages across all 3 terms.
     *
     * For each student×subject:
     *   average = round((term1_total + term2_total + term3_raw_total) / 3, 2)
     *
     * Missing prior-term scores are treated as 0.
     */
    private function computeCumulativeSubjectResults(int $classroomId, int $sessionId, int $termId): void
    {
        $enrolledStudentIds = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id');

        if ($enrolledStudentIds->isEmpty()) {
            return;
        }

        $termIds = $this->resolveTermIds($sessionId);

        // ── Get Term 1 & 2 subject results (already computed) ────────────────
        $priorResults = SubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->whereIn('term_id', [$termIds[1], $termIds[2]])
            ->whereIn('student_id', $enrolledStudentIds)
            ->get()
            ->groupBy(fn ($r) => $r->student_id . '-' . $r->subject_id . '-' . $r->term_id);

        // ── Get Term 3 raw score aggregates ──────────────────────────────────
        $term3Aggregates = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $enrolledStudentIds)
            ->groupBy('student_id', 'subject_id')
            ->selectRaw('student_id, subject_id, SUM(score) as subject_total')
            ->get();

        foreach ($term3Aggregates as $row) {
            $term3Raw = round((float) $row->subject_total, 2);

            // Look up Term 1 & 2 totals (default to 0 if missing)
            $key1 = $row->student_id . '-' . $row->subject_id . '-' . $termIds[1];
            $key2 = $row->student_id . '-' . $row->subject_id . '-' . $termIds[2];

            $term1Total = $priorResults->has($key1)
                ? (float) $priorResults[$key1]->first()->total
                : 0.0;

            $term2Total = $priorResults->has($key2)
                ? (float) $priorResults[$key2]->first()->total
                : 0.0;

            // Cumulative average
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
            $results = SubjectResult::where('classroom_id', $classroomId)
                ->where('subject_id', $subjectId)
                ->where('session_id', $sessionId)
                ->where('term_id', $termId)
                ->get();

            $ranked = $this->competitionRank($results, 'total', 'position');

            foreach ($ranked as $result) {
                $result->save();
            }
        }
    }

    /**
     * Step 4: Compute overall totals, averages, grades per student.
     */
    private function computeTermResults(int $classroomId, int $sessionId, int $termId): void
    {
        $studentAggregates = SubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM(total) as grand_total, COUNT(*) as subjects_count')
            ->get();

        foreach ($studentAggregates as $row) {
            $grandTotal    = round((float) $row->grand_total, 2);
            $subjectsCount = (int) $row->subjects_count;
            $average       = $subjectsCount > 0 ? round($grandTotal / $subjectsCount, 2) : 0;
            $grading       = $this->resolveGrade($average);

            TermResult::updateOrCreate(
                [
                    'student_id' => $row->student_id,
                    'session_id' => $sessionId,
                    'term_id'    => $termId,
                ],
                [
                    'classroom_id'  => $classroomId,
                    'subjects_count' => $subjectsCount,
                    'grand_total'   => $grandTotal,
                    'average'       => $average,
                    'grade'         => $grading['grade'],
                    'remark'        => $grading['remark'],
                ]
            );
        }
    }

    /**
     * Step 5: Competition-rank students by grand_total for overall position.
     */
    private function computeOverallPositions(int $classroomId, int $sessionId, int $termId): void
    {
        $results = TermResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->get();

        $ranked = $this->competitionRank($results, 'grand_total', 'overall_position');

        foreach ($ranked as $result) {
            $result->save();
        }
    }
}
