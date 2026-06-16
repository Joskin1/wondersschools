<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'classroom_id',
        'session_id',
        'term_id',
        'week_number',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'week_number' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the teacher who created this assignment.
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
     * Get the questions for this assignment.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(AssignmentQuestion::class);
    }

    /**
     * Get the submissions for this assignment.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /**
     * Get a student's submission for this assignment.
     */
    public function submissionForStudent(int $studentId): HasOne
    {
        return $this->hasOne(AssignmentSubmission::class)->where('student_id', $studentId);
    }

    /**
     * Scope to filter by active session and term.
     */
    public function scopeActive($query)
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        if (!$activeSession || !$activeTerm) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('session_id', $activeSession->id)
            ->where('term_id', $activeTerm->id);
    }
}
