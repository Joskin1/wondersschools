<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\User;
use App\Models\Student;
use Illuminate\Auth\Access\Response;

class ResultPolicy
{
    /**
     * Determine whether the user can view any results.
     */
    public function viewAny(User $user): bool
    {
        // Everyone can view results (scoped by their role)
        return true;
    }

    /**
     * Determine whether the user can view the result.
     */
    public function view(User $user, Result $result): bool
    {
        // If user is a student, they can only view their own results
        if ($user instanceof Student) {
            return $result->student_id === $user->id;
        }

        // If user has staff record (teacher), check if result is for their classroom student
        if ($user->staff) {
            $classroomIds = $user->staff->classrooms()->pluck('classrooms.id');
            return $classroomIds->contains($result->classroom_id);
        }

        // Admins can view all results
        return true;
    }

    /**
     * Determine whether the user can create results.
     * Results are auto-calculated, so only admins can manually create
     */
    public function create(User $user): bool
    {
        return !$user->staff;
    }

    /**
     * Determine whether the user can update the result.
     * Teachers can add remarks, admins can update everything
     */
    public function update(User $user, Result $result): bool
    {
        // If user has staff record (teacher), they can update remarks for their classroom
        if ($user->staff) {
            $classroomIds = $user->staff->classrooms()->pluck('classrooms.id');
            return $classroomIds->contains($result->classroom_id);
        }

        // Admins can update all results
        return true;
    }

    /**
     * Determine whether the user can delete the result.
     */
    public function delete(User $user, Result $result): bool
    {
        // Only admins can delete results
        return !$user->staff;
    }

    /**
     * Determine whether the user can restore the result.
     */
    public function restore(User $user, Result $result): bool
    {
        return !$user->staff;
    }

    /**
     * Determine whether the user can permanently delete the result.
     */
    public function forceDelete(User $user, Result $result): bool
    {
        return !$user->staff;
    }
}
