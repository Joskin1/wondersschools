<?php

namespace App\Services;

use App\Models\Result;
use App\Models\Score;
use App\Models\Student;

class ResultService
{
    /**
     * Calculate and save result for a student in a specific session and term.
     */
    public function calculateStudentResult(int $studentId, int $academicSessionId, int $termId): ?Result
    {
        $student = Student::find($studentId);
        
        if (!$student || !$student->classroom_id) {
            return null;
        }

        // Get all scores for this student in this session/term
        $scores = Score::where('student_id', $studentId)
            ->where('academic_session_id', $academicSessionId)
            ->where('term_id', $termId)
            ->with(['subject', 'assessmentType'])
            ->get();

        if ($scores->isEmpty()) {
            return null;
        }

        // Group scores by subject and calculate subject totals
        $subjectTotals = [];
        foreach ($scores as $score) {
            $subjectId = $score->subject_id;
            if (!isset($subjectTotals[$subjectId])) {
                $subjectTotals[$subjectId] = 0;
            }
            $subjectTotals[$subjectId] += $score->score;
        }

        // Calculate total score and average
        $totalScore = array_sum($subjectTotals);
        $subjectCount = count($subjectTotals);
        $averageScore = $subjectCount > 0 ? $totalScore / $subjectCount : 0;

        // Calculate grade based on average
        $grade = $this->calculateGrade($averageScore);

        // Calculate position within classroom
        $position = $this->calculatePosition($student->classroom_id, $academicSessionId, $termId, $totalScore);

        // Create or update result
        $result = Result::updateOrCreate(
            [
                'student_id' => $studentId,
                'academic_session_id' => $academicSessionId,
                'term_id' => $termId,
            ],
            [
                'classroom_id' => $student->classroom_id,
                'total_score' => $totalScore,
                'average_score' => round($averageScore, 2),
                'position' => $position,
                'grade' => $grade,
            ]
        );

        return $result;
    }

    /**
     * Calculate grade based on average score.
     */
    protected function calculateGrade(float $averageScore): string
    {
        if ($averageScore >= 90) {
            return 'A+';
        } elseif ($averageScore >= 80) {
            return 'A';
        } elseif ($averageScore >= 70) {
            return 'B';
        } elseif ($averageScore >= 60) {
            return 'C';
        } elseif ($averageScore >= 50) {
            return 'D';
        } elseif ($averageScore >= 40) {
            return 'E';
        } else {
            return 'F';
        }
    }

    /**
     * Calculate position within classroom based on total score.
     */
    protected function calculatePosition(int $classroomId, int $academicSessionId, int $termId, float $totalScore): int
    {
        // Count how many students in the same classroom have a higher total score
        $higherScoreCount = Result::where('classroom_id', $classroomId)
            ->where('academic_session_id', $academicSessionId)
            ->where('term_id', $termId)
            ->where('total_score', '>', $totalScore)
            ->count();

        return $higherScoreCount + 1;
    }

    /**
     * Recalculate positions for all students in a classroom for a specific session/term.
     */
    public function recalculateClassroomPositions(int $classroomId, int $academicSessionId, int $termId): void
    {
        $results = Result::where('classroom_id', $classroomId)
            ->where('academic_session_id', $academicSessionId)
            ->where('term_id', $termId)
            ->orderBy('total_score', 'desc')
            ->get();

        $position = 1;
        $previousScore = null;
        $sameScoreCount = 0;

        foreach ($results as $result) {
            if ($previousScore !== null && $result->total_score < $previousScore) {
                $position += $sameScoreCount;
                $sameScoreCount = 1;
            } else {
                $sameScoreCount++;
            }

            $result->update(['position' => $position]);
            $previousScore = $result->total_score;
        }
    }
}
