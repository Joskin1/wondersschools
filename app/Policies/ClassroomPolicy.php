<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class ClassroomPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'classrooms';
    }

    /**
     * Determine whether the user can delete the model.
     * Classrooms should never be deleted to preserve historical data.
     */
    public function delete(User $user, mixed $classroom): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, mixed $classroom): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, mixed $classroom): bool
    {
        return false;
    }
}
