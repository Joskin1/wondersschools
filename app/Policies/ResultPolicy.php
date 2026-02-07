<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ResultPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|Student $user): bool
    {
        // Admin users can view all results
        // Students can view their own results (handled in view method)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|Student $user, Result $result): bool
    {
        // If user is a Student, they can only view their own results
        if ($user instanceof Student) {
            return $result->student_id === $user->id;
        }
        
        // Admin users can view all results
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|Student $user): bool
    {
        // Only admin users can create results
        return $user instanceof User;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|Student $user, Result $result): bool
    {
        // Only admin users can update results
        return $user instanceof User;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|Student $user, Result $result): bool
    {
        // Only admin users can delete results
        return $user instanceof User;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|Student $user, Result $result): bool
    {
        // Only admin users can restore results
        return $user instanceof User;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|Student $user, Result $result): bool
    {
        // Only admin users can force delete results
        return $user instanceof User;
    }

    /**
     * Determine whether the user can generate results.
     */
    public function generate(User|Student $user): bool
    {
        // Only admin users can generate results
        return $user instanceof User;
    }
}
