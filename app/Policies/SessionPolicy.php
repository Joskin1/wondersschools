<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Session;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class SessionPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'academic_sessions';
    }

    /**
     * Sessions should never be deleted to preserve historical data.
     */
    public function delete(User $user, mixed $session): bool
    {
        return false;
    }

    public function restore(User $user, mixed $session): bool
    {
        return false;
    }

    public function forceDelete(User $user, mixed $session): bool
    {
        return false;
    }
}
