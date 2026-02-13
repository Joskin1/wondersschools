<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSubjectAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'classroom_id',
        'session_id',
        'term_id',
    ];

    /**
     * Get the teacher for this assignment.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the subject for this assignment.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the classroom for this assignment.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the session for this assignment.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the term for this assignment.
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }


    /**
     * Scope to get assignments for a specific teacher.
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope to get assignments for a specific classroom.
     */
    public function scopeForClassroom($query, int $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    /**
     * Scope to get assignments for a specific session.
     */
    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to get assignments for a specific term.
     */
    public function scopeForTerm($query, int $termId)
    {
        return $query->where('term_id', $termId);
    }

    /**
     * Scope to get assignments for the active session and term.
     */
    public function scopeActive($query)
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        if (!$activeSession || !$activeTerm) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        return $query->where('session_id', $activeSession->id)
            ->where('term_id', $activeTerm->id);
    }

    /**
     * Check if a teacher is assigned to a specific subject/classroom combination.
     */
    public static function isAssigned(int $teacherId, int $subjectId, int $classroomId, int $sessionId, int $termId): bool
    {
        return static::where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->exists();
    }
}
