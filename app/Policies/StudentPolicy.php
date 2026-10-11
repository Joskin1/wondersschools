<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class StudentPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'students';
    }
}
