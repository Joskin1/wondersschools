<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ScoreHead;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class ScoreHeadPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'score_heads';
    }
}
