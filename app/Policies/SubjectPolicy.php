<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageAcademics();
    }

    public function view(User $user, Subject $subject): bool
    {
        return $user->canManageAcademics();
    }

    public function create(User $user): bool
    {
        return $user->canManageAcademics();
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->canManageAcademics();
    }

    /**
     * Subjects should never be deleted to preserve historical data.
     */
    public function delete(User $user, Subject $subject): bool
    {
        return false;
    }

    public function restore(User $user, Subject $subject): bool
    {
        return false;
    }

    public function forceDelete(User $user, Subject $subject): bool
    {
        return false;
    }
}
