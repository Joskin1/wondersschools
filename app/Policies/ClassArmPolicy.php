<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClassArm;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class ClassArmPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'class_arms';
    }
}
