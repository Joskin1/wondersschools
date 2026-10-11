<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SubmissionWindow;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class SubmissionWindowPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'submission_windows';
    }

    /**
     * No deletions allowed - data preservation requirement.
     */
    public function delete(User $user, mixed $submissionWindow): bool
    {
        return false;
    }

    public function restore(User $user, mixed $submissionWindow): bool
    {
        return false;
    }

    public function forceDelete(User $user, mixed $submissionWindow): bool
    {
        return false;
    }
}
