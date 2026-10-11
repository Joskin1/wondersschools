<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClassScoreStructure;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class ClassScoreStructurePolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'class_score_structure';
    }

    public function update(User $user, mixed $structure): bool
    {
        if (! $user->canPerformAdminAction($this->getResourceKey(), 'update')) {
            return false;
        }

        // Locked structures require sudo to modify
        if ($structure instanceof ClassScoreStructure && $structure->locked && ! $user->isSudo()) {
            return false;
        }

        return true;
    }

    public function delete(User $user, mixed $structure): bool
    {
        // Never delete — preserves historical grading data
        return false;
    }

    public function lock(User $user, mixed $structure): bool
    {
        return $user->canManageAcademics();
    }

    public function unlock(User $user, mixed $structure): bool
    {
        // Only sudo can unlock — prevents mid-term grading changes
        return $user->isSudo();
    }
}
