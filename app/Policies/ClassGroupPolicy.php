<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClassGroup;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class ClassGroupPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'class_groups';
    }

    public function forceDelete(User $user, mixed $classGroup): bool
    {
        return false;
    }
}
