<?php

namespace App\Services;

use App\Models\Score;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    /**
     * Get scores query scoped for a teacher
     */
    public function getScoresQueryForTeacher(User $teacher, ?string $session = null, ?int $term = null): Builder
    {
        if (!$teacher->isTeacher() || !$teacher->staff) {
            return Score::query()->whereRaw('1 = 0'); // Return empty query
        }

        $query = Score::query()
            ->whereExists(function ($query) use ($teacher) {
                $query->select(DB::raw(1))
                    ->from('classroom_subject_teacher')
                    ->whereColumn('classroom_subject_teacher.subject_id', 'scores.subject_id')
                    ->whereColumn('classroom_subject_teacher.classroom_id', 'scores.classroom_id')
                    ->whereColumn('classroom_subject_teacher.session', 'scores.session')
                    ->where('classroom_subject_teacher.staff_id', $teacher->staff->id);
            });

        if ($session) {
            $query->where('session', $session);
        }

        if ($term) {
            $query->where('term', $term);
        }

        return $query;
    }

    /**
     * Get scores for a teacher
     */
    public function getScoresForTeacher(User $teacher, ?string $session = null, ?int $term = null): Collection
    {
        return $this->getScoresQueryForTeacher($teacher, $session, $term)->get();
    }

    /**
     * Validate if teacher is assigned to a specific subject-class combination
     */
    public function validateTeacherAssignment(
        User $teacher,
        int $subjectId,
        int $classroomId,
        string $session
    ): bool {
        if (!$teacher->isTeacher() || !$teacher->staff) {
            return false;
        }

        return DB::table('classroom_subject_teacher')
            ->where('staff_id', $teacher->staff->id)
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('session', $session)
            ->exists();
    }

    /**
     * Authorize score entry for a teacher
     */
    public function authorizeScoreEntry(User $teacher, array $scoreData): bool
    {
        if (!$teacher->isTeacher()) {
            return false;
        }

        return $this->validateTeacherAssignment(
            $teacher,
            $scoreData['subject_id'],
            $scoreData['classroom_id'],
            $scoreData['session']
        );
    }

    /**
     * Bulk update scores with validation
     */
    public function bulkUpdateScores(array $scores, User $teacher): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($scores as $scoreData) {
                // Validate teacher assignment
                if (!$this->authorizeScoreEntry($teacher, $scoreData)) {
                    $results['failed'][] = [
                        'data' => $scoreData,
                        'reason' => 'Not authorized for this subject-class combination',
                    ];
                    continue;
                }

                // Update or create score
                $score = Score::updateOrCreate(
                    [
                        'student_id' => $scoreData['student_id'],
                        'subject_id' => $scoreData['subject_id'],
                        'classroom_id' => $scoreData['classroom_id'],
                        'score_header_id' => $scoreData['score_header_id'],
                        'session' => $scoreData['session'],
                        'term' => $scoreData['term'],
                    ],
                    [
                        'value' => $scoreData['value'],
                    ]
                );

                $results['success'][] = $score;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Get assigned classrooms for a teacher with optional filters
     */
    public function getTeacherClassrooms(User $teacher, ?string $session = null, ?int $subjectId = null): Collection
    {
        if (!$teacher->isTeacher() || !$teacher->staff) {
            return collect();
        }

        $query = DB::table('classroom_subject_teacher')
            ->join('classrooms', 'classroom_subject_teacher.classroom_id', '=', 'classrooms.id')
            ->where('classroom_subject_teacher.staff_id', $teacher->staff->id)
            ->select('classrooms.*')
            ->distinct();

        if ($session) {
            $query->where('classroom_subject_teacher.session', $session);
        }

        if ($subjectId) {
            $query->where('classroom_subject_teacher.subject_id', $subjectId);
        }

        return $query->get();
    }

    /**
     * Get assigned subjects for a teacher with optional filters
     */
    public function getTeacherSubjects(User $teacher, ?string $session = null, ?int $classroomId = null): Collection
    {
        if (!$teacher->isTeacher() || !$teacher->staff) {
            return collect();
        }

        $query = DB::table('classroom_subject_teacher')
            ->join('subjects', 'classroom_subject_teacher.subject_id', '=', 'subjects.id')
            ->where('classroom_subject_teacher.staff_id', $teacher->staff->id)
            ->select('subjects.*')
            ->distinct();

        if ($session) {
            $query->where('classroom_subject_teacher.session', $session);
        }

        if ($classroomId) {
            $query->where('classroom_subject_teacher.classroom_id', $classroomId);
        }

        return $query->get();
    }
}
