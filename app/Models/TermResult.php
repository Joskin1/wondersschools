<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermResult extends Model
{
    protected $fillable = [
        'student_id', 'classroom_id',
        'session_id', 'term_id',
        'subjects_count', 'grand_total', 'average',
        'grade', 'remark', 'overall_position',
        'arm_position', 'arm_population',
        'department_position', 'department_population',
        'class_population',
        'times_present', 'class_teacher_comment', 'principal_comment',
        'is_finalized',
    ];

    protected $casts = [
        'subjects_count'        => 'integer',
        'grand_total'           => 'decimal:2',
        'average'               => 'decimal:2',
        'overall_position'      => 'integer',
        'arm_position'          => 'integer',
        'arm_population'        => 'integer',
        'department_position'   => 'integer',
        'department_population' => 'integer',
        'class_population'      => 'integer',
        'times_present'         => 'integer',
        'is_finalized'          => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForClassroom($query, int $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForTerm($query, int $termId)
    {
        return $query->where('term_id', $termId);
    }

    public function scopeFinalized($query)
    {
        return $query->where('is_finalized', true);
    }
}
