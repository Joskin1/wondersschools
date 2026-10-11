<?php

namespace App\Policies;

use App\Models\ClassScoreStructure;
use App\Models\User;

class ClassScoreStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminPage('class_score_structure');
    }

    public function view(User $user, ClassScoreStructure $structure): bool
    {
        return $user->canAccessAdminPage('class_score_structure');
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdminPage('class_score_structure');
    }

    public function update(User $user, ClassScoreStructure $structure): bool
    {
        if (! $user->canAccessAdminPage('class_score_structure')) {
            return false;
        }

        // Locked structures require sudo to modify
        if ($structure->locked && ! $user->isSudo()) {
            return false;
        }

        return true;
    }

    public function delete(User $user, ClassScoreStructure $structure): bool
    {
        // Never delete — preserves historical grading data
        return false;
    }

    public function lock(User $user, ClassScoreStructure $structure): bool
    {
        return $user->canManageAcademics();
    }

    public function unlock(User $user, ClassScoreStructure $structure): bool
    {
        // Only sudo can unlock — prevents mid-term grading changes
        return $user->isSudo();
    }
}
