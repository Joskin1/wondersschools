<?php

namespace App\Services;

use App\Models\ClassScoreStructure;
use App\Models\Score;
use App\Models\StudentEnrollment;
use App\Models\SubjectResult;
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
     * Everything runs inside one DB transaction for atomicity.
     *
     * @throws \RuntimeException if the score structure is not locked.
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

        DB::transaction(function () use ($classroomId, $sessionId, $termId) {
            // Step 1 + 2: Compute subject totals, grades, remarks
            $this->computeSubjectResults($classroomId, $sessionId, $termId);

            // Step 3: Subject positions (per subject, competition ranking)
            $this->computeSubjectPositions($classroomId, $sessionId, $termId);

            // Step 4: Overall totals, averages, grades
            $this->computeTermResults($classroomId, $sessionId, $termId);

            // Step 5: Overall positions (competition ranking)
            $this->computeOverallPositions($classroomId, $sessionId, $termId);
        });
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
     * Step 1+2: Compute and persist subject totals + grades.
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
