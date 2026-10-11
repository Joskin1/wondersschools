<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Term;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class TermPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'terms';
    }

    /**
     * Terms should never be deleted to preserve historical data.
     */
    public function delete(User $user, mixed $term): bool
    {
        return false;
    }

    public function migrate(User $user): bool
    {
        return $user->canManageAcademics();
    }

    public function restore(User $user, mixed $term): bool
    {
        return false;
    }

    public function forceDelete(User $user, mixed $term): bool
    {
        return false;
    }
}
