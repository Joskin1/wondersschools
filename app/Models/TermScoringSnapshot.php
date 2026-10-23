<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermScoringSnapshot extends Model
{
    protected $fillable = [
        'classroom_id',
        'session_id',
        'term_id',
        'mode',
        'midterm_weight',
        'raw_max',
        'calculation_method',
        'absent_policy',
        'result_decimal_places',
        'result_rounding_rule',
        'midterm_heads',
        'exam_heads',
        'snapshotted_by',
        'snapshotted_at',
    ];

    protected $casts = [
        'midterm_weight'        => 'integer',
        'raw_max'               => 'integer',
        'result_decimal_places' => 'integer',
        'midterm_heads'         => 'array',
        'exam_heads'            => 'array',
        'snapshotted_at'        => 'datetime',
    ];

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

    public function snapshottedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'snapshotted_by');
    }

    public function isMidtermMode(): bool
    {
        return $this->mode === 'midterm';
    }

    public function isLegacyMode(): bool
    {
        return $this->mode === 'legacy';
    }
}
