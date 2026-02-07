<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherAssignmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any teacher assignments.
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the teacher assignment.
     */
    public function view(User $user): bool
    {
        // Only admins can view teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create teacher assignments.
     */
    public function create(User $user): bool
    {
        // Only admins can create teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the teacher assignment.
     */
    public function update(User $user): bool
    {
        // Only admins can update teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the teacher assignment.
     */
    public function delete(User $user): bool
    {
        // Only admins can delete teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the teacher assignment.
     */
    public function restore(User $user): bool
    {
        // Only admins can restore teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the teacher assignment.
     */
    public function forceDelete(User $user): bool
    {
        // Only admins can force delete teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete any teacher assignments.
     */
    public function deleteAny(User $user): bool
    {
        // Only admins can bulk delete teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore any teacher assignments.
     */
    public function restoreAny(User $user): bool
    {
        // Only admins can bulk restore teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can force delete any teacher assignments.
     */
    public function forceDeleteAny(User $user): bool
    {
        // Only admins can bulk force delete teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can bulk assign teachers.
     */
    public function bulkAssign(User $user): bool
    {
        // Only admins can perform bulk assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can export teacher assignments.
     */
    public function export(User $user): bool
    {
        // Only admins can export teacher assignments
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can import teacher assignments.
     */
    public function import(User $user): bool
    {
        // Only admins can import teacher assignments
        return $user->isAdmin();
    }
}
