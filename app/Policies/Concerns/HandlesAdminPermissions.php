<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;

trait HandlesAdminPermissions
{
    /**
     * The admin permission registry key for this resource.
     */
    abstract protected function getResourceKey(): string;

    /**
     * Determine whether the user can view any models (list/index).
     */
    public function viewAny(User $user): bool
    {
        return $user->canPerformAdminAction($this->getResourceKey(), 'viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, mixed $model): bool
    {
        return $user->canPerformAdminAction($this->getResourceKey(), 'view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canPerformAdminAction($this->getResourceKey(), 'create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, mixed $model): bool
    {
        return $user->canPerformAdminAction($this->getResourceKey(), 'update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, mixed $model): bool
    {
        return $user->canPerformAdminAction($this->getResourceKey(), 'delete');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->canPerformAdminAction($this->getResourceKey(), 'delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, mixed $model): bool
    {
        return $user->canPerformAdminAction($this->getResourceKey(), 'update');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, mixed $model): bool
    {
        return $user->canPerformAdminAction($this->getResourceKey(), 'delete');
    }
}
