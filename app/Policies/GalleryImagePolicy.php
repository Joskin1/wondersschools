<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GalleryImage;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class GalleryImagePolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'gallery_images';
    }
}
