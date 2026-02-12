<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassTeacherAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'class_id',
        'session_id',
    ];

    /**
     * Get the teacher for this assignment.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the classroom for this assignment.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Get the session for this assignment.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Scope to get assignments for a specific teacher.
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope to get assignments for the active session.
     */
    public function scopeActive($query)
    {
        $activeSession = Session::active()->first();

        if (!$activeSession) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        return $query->where('session_id', $activeSession->id);
    }

    /**
     * Check if a teacher is the class teacher for a specific classroom in a session.
     */
    public static function isClassTeacher(int $teacherId, int $classroomId, int $sessionId): bool
    {
        return static::where('teacher_id', $teacherId)
            ->where('class_id', $classroomId)
            ->where('session_id', $sessionId)
            ->exists();
    }
}
