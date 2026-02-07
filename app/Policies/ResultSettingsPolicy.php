<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class ResultSettingsPolicy
{
    /**
     * Determine whether the user can view any settings.
     */
    public function viewAny(User|Student $user): bool
    {
        // Only admin users can view settings
        return $user instanceof User;
    }

    /**
     * Determine whether the user can view settings.
     */
    public function view(User|Student $user): bool
    {
        // Only admin users can view settings
        return $user instanceof User;
    }

    /**
     * Determine whether the user can create settings.
     */
    public function create(User|Student $user): bool
    {
        // Only admin users can create settings
        return $user instanceof User;
    }

    /**
     * Determine whether the user can update settings.
     */
    public function update(User|Student $user): bool
    {
        // Only admin users can update settings
        return $user instanceof User;
    }

    /**
     * Determine whether the user can delete settings.
     */
    public function delete(User|Student $user): bool
    {
        // Only admin users can delete settings
        return $user instanceof User;
    }
}
