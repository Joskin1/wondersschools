<?php

namespace App\Policies;

use App\Models\Score;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScorePolicy
{
    /**
     * Determine whether the user can view any scores.
     */
    public function viewAny(User $user): bool
    {
        // Teachers and admins can view scores
        return true;
    }

    /**
     * Determine whether the user can view the score.
     */
    public function view(User $user, Score $score): bool
    {
        // If user has staff record, check if they're assigned to this classroom/subject
        if ($user->staff) {
            return $this->isTeacherAssignedToScore($user, $score);
        }

        // Admins can view all scores
        return true;
    }

    /**
     * Determine whether the user can create scores.
     */
    public function create(User $user): bool
    {
        // Both teachers and admins can create scores
        return true;
    }

    /**
     * Determine whether the user can update the score.
     */
    public function update(User $user, Score $score): bool
    {
        // If user has staff record, check if they're assigned to this classroom/subject
        if ($user->staff) {
            return $this->isTeacherAssignedToScore($user, $score);
        }

        // Admins can update all scores
        return true;
    }

    /**
     * Determine whether the user can delete the score.
     */
    public function delete(User $user, Score $score): bool
    {
        // Only admins can delete scores
        return !$user->staff;
    }

    /**
     * Check if teacher is assigned to the classroom and subject for this score.
     */
    protected function isTeacherAssignedToScore(User $user, Score $score): bool
    {
        if (!$user->staff) {
            return false;
        }

        $student = $score->student;
        if (!$student) {
            return false;
        }

        // Check if teacher is assigned to this classroom and subject
        $isAssigned = DB::table('classroom_subject_teacher')
            ->where('classroom_id', $student->classroom_id)
            ->where('subject_id', $score->subject_id)
            ->where('staff_id', $user->staff->id)
            ->exists();

        return $isAssigned;
    }

    /**
     * Determine if teacher can input scores for a specific classroom and subject.
     */
    public function inputScoresFor(User $user, int $classroomId, int $subjectId): bool
    {
        // Admins can input for any classroom/subject
        if (!$user->staff) {
            return true;
        }

        // Check if teacher is assigned to this classroom and subject
        return DB::table('classroom_subject_teacher')
            ->where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId)
            ->where('staff_id', $user->staff->id)
            ->exists();
    }
}
