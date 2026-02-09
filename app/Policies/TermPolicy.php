<?php

namespace App\Policies;

use App\Models\Term;
use App\Models\User;

class TermPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins and teachers can view terms
        return $user->canManageAcademics();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Term $term): bool
    {
        return $user->canManageAcademics();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins can create terms (auto-created with sessions)
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Term $term): bool
    {
        // Only admins can update terms
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     * Terms should never be deleted to preserve historical data.
     */
    public function delete(User $user, Term $term): bool
    {
        return false;
    }

    /**
     * Determine whether the user can migrate terms.
     */
    public function migrate(User $user): bool
    {
        // Admins and sudo users can migrate terms
        return $user->canManageAcademics();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Term $term): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Term $term): bool
    {
        return false;
    }
}
