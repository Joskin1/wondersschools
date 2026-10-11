<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class TeacherSubjectAssignmentPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'subject_teachers';
    }
}
