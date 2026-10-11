<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StudentEnrollment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'student_id',
        'classroom_id',
        'class_arm_id',
        'department_id',
        'session_id',
    ];

    /**
     * Get the student that owns the enrollment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the classroom for this enrollment.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the class arm for this enrollment.
     */
    public function classArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class, 'class_arm_id');
    }

    /**
     * Get the department for this enrollment.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the full display name for this class enrollment (e.g. "JSS 1 — Gold" or "JSS 1").
     */
    public function getFullClassName(): string
    {
        $className = $this->classroom?->name ?? '—';
        if ($this->classArm) {
            return "{$className} — {$this->classArm->name}";
        }
        return $className;
    }

    /**
     * Get the session for this enrollment.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Scope a query to only include enrollments for a specific session.
     */
    public function scopeForSession(Builder $query, int $sessionId): void
    {
        $query->where('session_id', $sessionId);
    }
}
