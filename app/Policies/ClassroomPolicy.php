<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    /**
     * Determine whether the user can view any classrooms.
     */
    public function viewAny(User $user): bool
    {
        // Everyone can view classrooms (scoped by TeacherScope)
        return true;
    }

    /**
     * Determine whether the user can view the classroom.
     */
    public function view(User $user, Classroom $classroom): bool
    {
        // If user has staff record, check if they're assigned to this classroom
        if ($user->staff) {
            $classroomIds = $user->staff->classrooms()->pluck('classrooms.id');
            return $classroomIds->contains($classroom->id);
        }

        // Admins can view all classrooms
        return true;
    }

    /**
     * Determine whether the user can create classrooms.
     */
    public function create(User $user): bool
    {
        // Only admins can create classrooms
        return !$user->staff;
    }

    /**
     * Determine whether the user can update the classroom.
     */
    public function update(User $user, Classroom $classroom): bool
    {
        // Only admins can update classrooms
        return !$user->staff;
    }

    /**
     * Determine whether the user can delete the classroom.
     */
    public function delete(User $user, Classroom $classroom): bool
    {
        // Only admins can delete classrooms
        return !$user->staff;
    }

    /**
     * Determine whether the user can restore the classroom.
     */
    public function restore(User $user, Classroom $classroom): bool
    {
        return !$user->staff;
    }

    /**
     * Determine whether the user can permanently delete the classroom.
     */
    public function forceDelete(User $user, Classroom $classroom): bool
    {
        return !$user->staff;
    }
}
