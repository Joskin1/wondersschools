<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultPin extends Model
{
    protected $table = 'result_pins';

    protected $fillable = [
        'batch_id',
        'serial_number',
        'pin',
        'session_id',
        'term_id',
        'student_id',
        'is_used',
        'used_at',
    ];

    protected $casts = [
        'batch_id' => 'integer',
        'session_id' => 'integer',
        'term_id' => 'integer',
        'student_id' => 'integer',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ResultPinBatch::class, 'batch_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function scopeUnused(Builder $query): Builder
    {
        return $query->where('is_used', false);
    }

    public function scopeUsed(Builder $query): Builder
    {
        return $query->where('is_used', true);
    }

    public function scopeForTerm(Builder $query, int $sessionId, int $termId): Builder
    {
        return $query->where('session_id', $sessionId)->where('term_id', $termId);
    }
}
