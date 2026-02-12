<?php

namespace App\Policies;

use App\Models\LessonNote;
use App\Models\User;
use App\Models\TeacherSubjectAssignment;
use App\Models\ClassTeacherAssignment;
use App\Services\LessonNoteCache;

class LessonNotePolicy
{
    /**
     * Determine if the user can view any lesson notes.
     */
    public function viewAny(User $user): bool
    {
        // Admins and sudo can view all
        // Teachers can view their own
        return in_array($user->role, ['admin', 'sudo', 'teacher']);
    }

    /**
     * Determine if the user can view the lesson note.
     */
    public function view(User $user, LessonNote $lessonNote): bool
    {
        // Admins and sudo can view all
        if (in_array($user->role, ['admin', 'sudo'])) {
            return true;
        }

        // Teachers can view their own
        if ($user->role === 'teacher') {
            if ($lessonNote->teacher_id === $user->id) {
                return true;
            }
            
            // Class teachers can view all notes in their class
            if (ClassTeacherAssignment::isClassTeacher(
                $user->id, 
                $lessonNote->classroom_id, 
                $lessonNote->session_id
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user can create lesson notes.
     */
    public function create(User $user): bool
    {
        // Only teachers can create lesson notes
        return $user->role === 'teacher';
    }

    /**
     * Determine if the user can update the lesson note.
     */
    public function update(User $user, LessonNote $lessonNote): bool
    {
        // Admins can always update (for review/approval)
        if (in_array($user->role, ['admin', 'sudo'])) {
            return true;
        }

        // Teachers can only update their own pending notes
        if ($user->role === 'teacher') {
            return $lessonNote->teacher_id === $user->id 
                && $lessonNote->status === 'pending';
        }

        return false;
    }

    /**
     * Determine if the user can delete the lesson note.
     * 
     * CRITICAL: No deletions allowed - data preservation requirement
     */
    public function delete(User $user, LessonNote $lessonNote): bool
    {
        return false; // Never allow deletions
    }

    /**
     * Determine if the user can approve the lesson note.
     */
    public function approve(User $user, LessonNote $lessonNote): bool
    {
        return in_array($user->role, ['admin', 'sudo']);
    }

    /**
     * Determine if the user can reject the lesson note.
     */
    public function reject(User $user, LessonNote $lessonNote): bool
    {
        return in_array($user->role, ['admin', 'sudo']);
    }

    /**
     * Check if a teacher can upload for a specific subject/classroom combination.
     */
    public static function canUploadFor(User $user, int $subjectId, int $classroomId, int $sessionId, int $termId): bool
    {
        if ($user->role !== 'teacher') {
            return false;
        }

        // Check if user is class teacher for this classroom
        if (ClassTeacherAssignment::isClassTeacher($user->id, $classroomId, $sessionId)) {
            return true;
        }

        // Check subject teacher assignment
        return TeacherSubjectAssignment::isAssigned(
            $user->id,
            $subjectId,
            $classroomId,
            $sessionId,
            $termId
        );
    }
}
