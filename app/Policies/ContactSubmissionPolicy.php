<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContactSubmission;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class ContactSubmissionPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'contact_submissions';
    }
}
