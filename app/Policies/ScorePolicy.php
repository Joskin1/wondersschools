<?php

namespace App\Policies;

use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ScorePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|Student $user): bool
    {
        // Admin and teachers can view all scores
        // Students can view their own scores
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|Student $user, Score $score): bool
    {
        // If user is a Student, they can only view their own scores
        if ($user instanceof Student) {
            return $score->student_id === $user->id;
        }
        
        // Admin users can view all scores
        if ($user->isAdmin()) {
            return true;
        }
        
        // Teachers can only view scores for assigned subject-class combinations
        if ($user->isTeacher() && $user->staff) {
            return \Illuminate\Support\Facades\DB::table('classroom_subject_teacher')
                ->where('staff_id', $user->staff->id)
                ->where('subject_id', $score->subject_id)
                ->where('classroom_id', $score->classroom_id)
                ->where('session', $score->session)
                ->exists();
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|Student $user): bool
    {
        // Only admin and teachers can create scores
        return $user instanceof User && ($user->isAdmin() || $user->isTeacher());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|Student $user, Score $score): bool
    {
        // Students cannot update scores
        if ($user instanceof Student) {
            return false;
        }

        // Admin users can update all scores
        if ($user->isAdmin()) {
            return true;
        }

        // Teachers can only update scores for assigned subject-class combinations
        if ($user->isTeacher() && $user->staff) {
            return \Illuminate\Support\Facades\DB::table('classroom_subject_teacher')
                ->where('staff_id', $user->staff->id)
                ->where('subject_id', $score->subject_id)
                ->where('classroom_id', $score->classroom_id)
                ->where('session', $score->session)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|Student $user, Score $score): bool
    {
        if (!($user instanceof User)) {
            return false;
        }

        // Admins can delete any score
        if ($user->isAdmin()) {
            return true;
        }

        // Teachers can only delete scores for their assigned subjects/classrooms
        if ($user->isTeacher()) {
            $scoreService = app(ScoreService::class);
            return $scoreService->validateTeacherAssignment(
                $user,
                $score->subject_id,
                $score->classroom_id,
                $score->session
            );
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|Student $user, Score $score): bool
    {
        // Only admin users can restore scores
        return $user instanceof User;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|Student $user, Score $score): bool
    {
        // Only admin users can force delete scores
        return $user instanceof User;
    }

    /**
     * Determine whether the user can import scores.
     */
    public function import(User|Student $user): bool
    {
        // Only admin and teachers can import scores
        return $user instanceof User;
    }

    /**
     * Determine whether the user can export scores.
     */
    public function export(User|Student $user): bool
    {
        // Only admin and teachers can export scores
        return $user instanceof User;
    }
}
