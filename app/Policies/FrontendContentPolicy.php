<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FrontendContent;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class FrontendContentPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'frontend_content';
    }
}
