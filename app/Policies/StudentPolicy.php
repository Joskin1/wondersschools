<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any students.
     * Teachers can only see students in their assigned classrooms.
     */
    public function viewAny(User $user): bool
    {
        // If user has staff record with assigned classrooms, they can view
        if ($user->staff && $user->staff->classrooms()->exists()) {
            return true;
        }

        // Admins (users without staff records) can view all
        return !$user->staff;
    }

    /**
     * Determine whether the user can view the student.
     * Teachers can only view students in their assigned classrooms.
     */
    public function view(User $user, Student $student): bool
    {
        // If user has staff record, check if student is in their classroom
        if ($user->staff) {
            $classroomIds = $user->staff->classrooms()->pluck('classrooms.id');
            return $classroomIds->contains($student->classroom_id);
        }

        // Admins can view all students
        return true;
    }

    /**
     * Determine whether the user can create students.
     * Only admins can create students.
     */
    public function create(User $user): bool
    {
        // Only admins (users without staff records) can create
        return !$user->staff;
    }

    /**
     * Determine whether the user can update the student.
     * Teachers can update students in their assigned classrooms.
     */
    public function update(User $user, Student $student): bool
    {
        // If user has staff record, check if student is in their classroom
        if ($user->staff) {
            $classroomIds = $user->staff->classrooms()->pluck('classrooms.id');
            return $classroomIds->contains($student->classroom_id);
        }

        // Admins can update all students
        return true;
    }

    /**
     * Determine whether the user can delete the student.
     * Only admins can delete students.
     */
    public function delete(User $user, Student $student): bool
    {
        // Only admins can delete
        return !$user->staff;
    }

    /**
     * Determine whether the user can restore the student.
     */
    public function restore(User $user, Student $student): bool
    {
        return !$user->staff;
    }

    /**
     * Determine whether the user can permanently delete the student.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        return !$user->staff;
    }
}
