<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Grading;
use App\Models\Result;
use App\Models\Score;
use App\Models\ScoreHeader;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResultComputationService
{
    /**
     * Compute results for an entire classroom
     */
    public function computeResults(int $classroomId, string $session, int $term, ?string $settingsName = null): Collection
    {
        $classroom = Classroom::findOrFail($classroomId);
        $students = $classroom->students;
        
        $results = collect();
        
        foreach ($students as $student) {
            $result = $this->computeStudentResult($student->id, $classroomId, $session, $term, $settingsName);
            if ($result) {
                $results->push($result);
            }
        }
        
        // Inject positions after computing all results
        return $this->injectPositions($results);
    }

    /**
     * Compute result for a single student
     */
    public function computeStudentResult(
        int $studentId,
        int $classroomId,
        string $session,
        int $term,
        ?string $settingsName = null
    ): ?Result {
        $student = Student::findOrFail($studentId);
        $subjects = $this->getStudentSubjects($studentId, $classroomId);
        
        if ($subjects->isEmpty()) {
            return null;
        }
        
        $scoreHeaders = ScoreHeader::getHeaders($classroomId, $session, $term);
        $resultData = [];
        $totalScore = 0;
        $subjectCount = 0;
        
        foreach ($subjects as $subject) {
            $subjectData = $this->computeSubjectData($studentId, $subject->id, $session, $term, $scoreHeaders);
            $resultData[$subject->id] = $subjectData;
            
            if ($subjectData['total'] > 0) {
                $totalScore += $subjectData['total'];
                $subjectCount++;
            }
        }
        
        $averageScore = $subjectCount > 0 ? round($totalScore / $subjectCount, 2) : 0;
        $cacheKey = Result::generateCacheKey($studentId, $session, $term);
        
        // Create or update result
        $result = Result::updateOrCreate(
            [
                'student_id' => $studentId,
                'classroom_id' => $classroomId,
                'session' => $session,
                'term' => $term,
            ],
            [
                'cache_key' => $cacheKey,
                'settings_name' => $settingsName,
                'result_data' => $resultData,
                'total_score' => $totalScore,
                'average_score' => $averageScore,
                'overall_average' => $averageScore,
                'generated_at' => now(),
            ]
        );
        
        return $result->fresh();
    }

    /**
     * Compute subject data for a student
     */
    private function computeSubjectData(int $studentId, int $subjectId, string $session, int $term, array $scoreHeaders): array
    {
        $data = [
            'scores' => [],
            'total' => 0,
            'grade' => null,
            'remark' => null,
            'position' => null,
        ];
        
        // Get scores for each header
        foreach ($scoreHeaders as $header) {
            $score = Score::where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('score_header_id', $header['id'])
                ->where('session', $session)
                ->where('term', $term)
                ->first();
            
            $data['scores'][$header['name']] = $score ? (float) $score->value : 0;
            $data['total'] += $data['scores'][$header['name']];
        }
        
        // Get grade
        $grading = Grading::getGradeForScore($data['total'], $subjectId, $session);
        if ($grading) {
            $data['grade'] = $grading->letter;
            $data['remark'] = $grading->remark;
        }
        
        return $data;
    }

    /**
     * Get subjects for a student in a classroom
     */
    private function getStudentSubjects(int $studentId, int $classroomId): Collection
    {
        // This assumes students have subjects through their classroom
        // Adjust based on your actual relationship structure
        return Subject::whereHas('classrooms', function ($query) use ($classroomId) {
            $query->where('classrooms.id', $classroomId);
        })->get();
    }

    /**
     * Inject positions into results with tie logic
     */
    private function injectPositions(Collection $results): Collection
    {
        // Sort by average score descending
        $sorted = $results->sortByDesc('average_score')->values();
        
        $position = 1;
        $previousScore = null;
        $studentsWithSameScore = 0;
        
        foreach ($sorted as $index => $result) {
            if ($previousScore !== null && $result->average_score < $previousScore) {
                $position += $studentsWithSameScore;
                $studentsWithSameScore = 1;
            } else {
                $studentsWithSameScore++;
            }
            
            $result->update([
                'position' => $position,
                'position_in_class' => $position,
            ]);
            
            $previousScore = $result->average_score;
        }
        
        return $sorted;
    }

    /**
     * Compute cell value for a specific column type
     */
    public function computeCellValue(Student $student, Subject $subject, string $columnType, string $session, int $term): mixed
    {
        return match($columnType) {
            'scoreHeader' => $this->getScoreHeaderValue($student->id, $subject->id, $session, $term),
            'termTotalScoreObtained' => Score::getTotalScore($student->id, $subject->id, $session, $term),
            'averageOfDisplayedScores' => $this->getAverageScore($student->id, $subject->id, $session, $term),
            'currentTermPercentageScore' => $this->getPercentageScore($student->id, $subject->id, $session, $term),
            'averagePercentageScoreForAllTerms' => $this->getCumulativePercentage($student->id, $subject->id, $session),
            'grade' => $this->getGrade($student->id, $subject->id, $session, $term),
            'subjectPosition' => $this->getSubjectPosition($student->id, $subject->id, $session, $term),
            default => null,
        };
    }

    /**
     * Get score header value
     */
    private function getScoreHeaderValue(int $studentId, int $subjectId, string $session, int $term): float
    {
        return Score::getTotalScore($studentId, $subjectId, $session, $term);
    }

    /**
     * Get average score
     */
    private function getAverageScore(int $studentId, int $subjectId, string $session, int $term): float
    {
        $total = Score::getTotalScore($studentId, $subjectId, $session, $term);
        $count = Score::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->where('term', $term)
            ->count();
        
        return $count > 0 ? round($total / $count, 2) : 0;
    }

    /**
     * Get percentage score
     */
    private function getPercentageScore(int $studentId, int $subjectId, string $session, int $term): float
    {
        $total = Score::getTotalScore($studentId, $subjectId, $session, $term);
        return round($total, 2); // Assuming total is already out of 100
    }

    /**
     * Get cumulative percentage across all terms
     */
    private function getCumulativePercentage(int $studentId, int $subjectId, string $session): float
    {
        $scores = Score::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->get();
        
        if ($scores->isEmpty()) {
            return 0;
        }
        
        $totalScore = $scores->sum('value');
        $count = $scores->count();
        
        return $count > 0 ? round($totalScore / $count, 2) : 0;
    }

    /**
     * Get grade for subject
     */
    private function getGrade(int $studentId, int $subjectId, string $session, int $term): ?string
    {
        $total = Score::getTotalScore($studentId, $subjectId, $session, $term);
        $grading = Grading::getGradeForScore($total, $subjectId, $session);
        
        return $grading?->letter;
    }

    /**
     * Get subject position
     */
    private function getSubjectPosition(int $studentId, int $subjectId, string $session, int $term): ?int
    {
        // Get all students' scores for this subject
        $scores = DB::table('scores')
            ->select('student_id', DB::raw('SUM(value) as total'))
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->where('term', $term)
            ->groupBy('student_id')
            ->orderByDesc('total')
            ->get();
        
        $position = 1;
        foreach ($scores as $index => $score) {
            if ($score->student_id == $studentId) {
                return $position;
            }
            $position++;
        }
        
        return null;
    }
}
