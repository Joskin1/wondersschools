<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class PostPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'posts';
    }
}
