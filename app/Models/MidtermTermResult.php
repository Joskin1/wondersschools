<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidtermTermResult extends Model
{
    protected $fillable = [
        'student_id',
        'classroom_id',
        'session_id',
        'term_id',
        'subjects_count',
        'total_obtainable',
        'total_obtained',
        'percentage',
        'grade',
        'remark',
        'teacher_comment',
        'head_teacher_comment',
    ];

    protected $casts = [
        'subjects_count'   => 'integer',
        'total_obtainable' => 'decimal:2',
        'total_obtained'   => 'decimal:2',
        'percentage'       => 'decimal:2',
    ];

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
}
