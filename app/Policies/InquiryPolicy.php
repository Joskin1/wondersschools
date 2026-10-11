<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class InquiryPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'inquiries';
    }
}
