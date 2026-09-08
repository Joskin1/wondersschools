<?php

namespace App\Policies;

use App\Models\LessonPlan;
use App\Models\User;

class LessonPlanPolicy
{
    public function viewAny(User $user): bool
    {
        // Admins, sudo, and teachers can view
        return in_array($user->role, ['admin', 'sudo', 'teacher']);
    }

    public function view(User $user, LessonPlan $lessonPlan): bool
    {
        // Admins and sudo can view all
        if (in_array($user->role, ['admin', 'sudo'])) {
            return true;
        }

        // Teachers can only view their own
        if ($user->role === 'teacher') {
            return $lessonPlan->teacher_id === $user->id;
        }

        // Students CANNOT view lesson plans under any circumstances
        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === 'teacher';
    }

    public function update(User $user, LessonPlan $lessonPlan): bool
    {
        if (in_array($user->role, ['admin', 'sudo'])) {
            return true;
        }

        if ($user->role === 'teacher') {
            return $lessonPlan->teacher_id === $user->id
                && in_array($lessonPlan->status, ['draft', 'rejected']);
        }

        return false;
    }

    public function delete(User $user, LessonPlan $lessonPlan): bool
    {
        return false; // Data preservation
    }

    public function approve(User $user, LessonPlan $lessonPlan): bool
    {
        return in_array($user->role, ['admin', 'sudo']);
    }

    public function reject(User $user, LessonPlan $lessonPlan): bool
    {
        return in_array($user->role, ['admin', 'sudo']);
    }
}
