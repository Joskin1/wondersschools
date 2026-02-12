<?php

namespace App\Policies;

use App\Models\SubmissionWindow;
use App\Models\User;

class SubmissionWindowPolicy
{
    /**
     * Determine if the user can view any submission windows.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'sudo']);
    }

    /**
     * Determine if the user can view the submission window.
     */
    public function view(User $user, SubmissionWindow $submissionWindow): bool
    {
        return in_array($user->role, ['admin', 'sudo']);
    }

    /**
     * Determine if the user can create submission windows.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'sudo']);
    }

    /**
     * Determine if the user can update the submission window.
     */
    public function update(User $user, SubmissionWindow $submissionWindow): bool
    {
        return in_array($user->role, ['admin', 'sudo']);
    }

    /**
     * Determine if the user can delete the submission window.
     * 
     * CRITICAL: No deletions allowed - data preservation requirement
     */
    public function delete(User $user, SubmissionWindow $submissionWindow): bool
    {
        return false; // Never allow deletions
    }
}
