<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidtermSubjectResult extends Model
{
    protected $fillable = [
        'student_id',
        'classroom_id',
        'subject_id',
        'session_id',
        'term_id',
        'raw_score',
        'student_raw_max',
        'midterm_weight',
        'scaled_score',
        'percentage',
        'grade',
        'remark',
        'status',
    ];

    protected $casts = [
        'raw_score'       => 'decimal:2',
        'student_raw_max' => 'integer',
        'midterm_weight'  => 'integer',
        'scaled_score'    => 'decimal:2',
        'percentage'      => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }

    public function isMissing(): bool
    {
        return $this->status === 'missing';
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }
}
