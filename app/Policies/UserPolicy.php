<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesAdminPermissions;

class UserPolicy
{
    use HandlesAdminPermissions;

    protected function getResourceKey(): string
    {
        return 'users';
    }

    /**
     * Determine whether the user can view the user model.
     * Non-sudo users can never view sudo accounts.
     */
    public function view(User $user, mixed $model): bool
    {
        if ($model instanceof User && $model->isSudo() && ! $user->isSudo()) {
            return false;
        }

        return $user->canPerformAdminAction($this->getResourceKey(), 'view');
    }

    /**
     * Determine whether the user can update the user model.
     * Sudo cannot be updated by non-sudo.
     * Designated Principal cannot be modified by secondary staff.
     */
    public function update(User $user, mixed $model): bool
    {
        if ($model instanceof User) {
            if ($model->isSudo() && ! $user->isSudo()) {
                return false;
            }

            if ($model->isPrimaryAdmin() && ! $user->isSudo()) {
                return false;
            }
        }

        return $user->canPerformAdminAction($this->getResourceKey(), 'update');
    }

    /**
     * Determine whether the user can delete the user model.
     * Sudo accounts and designated Principal cannot be deleted. Users cannot delete themselves.
     */
    public function delete(User $user, mixed $model): bool
    {
        if ($model instanceof User) {
            if ($model->isSudo() || $model->isPrimaryAdmin()) {
                return false;
            }

            if ($model->id === $user->id) {
                return false;
            }
        }

        return $user->canPerformAdminAction($this->getResourceKey(), 'delete');
    }
}
