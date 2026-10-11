<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class SubjectPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'subjects';
    }

    /**
     * Subjects should never be deleted to preserve historical data.
     */
    public function delete(User $user, mixed $subject): bool
    {
        return false;
    }

    public function restore(User $user, mixed $subject): bool
    {
        return false;
    }

    public function forceDelete(User $user, mixed $subject): bool
    {
        return false;
    }
}
