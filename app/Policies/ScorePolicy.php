<?php

namespace App\Policies;

use App\Models\ClassTeacherAssignment;
use App\Models\Score;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;

class ScorePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return $user->canAccessAdminPage('enter_scores') || $user->canAccessAdminPage('process_class_results');
        }

        return $user->isSudo() || $user->isTeacher();
    }

    public function view(User $user, Score $score): bool
    {
        if ($user->isAdmin()) {
            return $user->canAccessAdminPage('enter_scores') || $user->canAccessAdminPage('process_class_results');
        }

        if ($user->isSudo()) {
            return true;
        }

        if ($user->isTeacher()) {
            return $user->id === $score->teacher_id
                || $this->isAuthorizedForSubjectInClass(
                    $user,
                    $score->subject_id,
                    $score->classroom_id,
                    $score->session_id,
                    $score->term_id
                );
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return $user->canAccessAdminPage('enter_scores');
        }

        return $user->isSudo() || $user->isTeacher();
    }

    public function update(User $user, Score $score): bool
    {
        if ($user->isAdmin()) {
            return $user->canAccessAdminPage('enter_scores');
        }

        if ($user->isSudo()) {
            return true;
        }

        if ($user->isTeacher()) {
            return $this->isAuthorizedForSubjectInClass(
                $user,
                $score->subject_id,
                $score->classroom_id,
                $score->session_id,
                $score->term_id
            );
        }

        return false;
    }

    public function delete(User $user, Score $score): bool
    {
        if ($user->isAdmin()) {
            return $user->canAccessAdminPage('enter_scores');
        }

        return $user->isSudo();
    }

    /**
     * Gate-check for bulk score entry on a specific subject/class combination.
     * Server-side only — never rely on UI filtering alone.
     */
    public function enterScore(
        User $user,
        int $subjectId,
        int $classroomId,
        int $sessionId,
        int $termId
    ): bool {
        if ($user->isAdmin()) {
            return $user->canAccessAdminPage('enter_scores');
        }

        if ($user->isSudo()) {
            return true;
        }

        if (! $user->isTeacher()) {
            return false;
        }

        return $this->isAuthorizedForSubjectInClass($user, $subjectId, $classroomId, $sessionId, $termId);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function isAuthorizedForSubjectInClass(
        User $user,
        int $subjectId,
        int $classroomId,
        int $sessionId,
        int $termId
    ): bool {
        // Class teacher → any subject in the class
        if (ClassTeacherAssignment::isClassTeacher($user->id, $classroomId, $sessionId)) {
            return true;
        }

        // Subject teacher → only their assigned subject(s)
        return TeacherSubjectAssignment::isAssigned($user->id, $subjectId, $classroomId, $sessionId, $termId);
    }
}
