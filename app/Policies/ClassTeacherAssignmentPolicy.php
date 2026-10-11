<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClassTeacherAssignment;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class ClassTeacherAssignmentPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'class_teachers';
    }
}
