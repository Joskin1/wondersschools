<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class DepartmentPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'departments';
    }
}
