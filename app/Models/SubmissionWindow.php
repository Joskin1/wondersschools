<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'term_id',
        'week_number',
    ];

    protected $casts = [
        'week_number' => 'integer',
    ];

    /**
     * Get the session for this window.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the term for this window.
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Scope to get windows for a specific week.
     */
    public function scopeForWeek($query, int $sessionId, int $termId, int $weekNumber)
    {
        return $query->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->where('week_number', $weekNumber);
    }

    /**
     * Get the cache key for this window.
     */
    public function getCacheKey(): string
    {
        return "submission_window:{$this->session_id}:{$this->term_id}:{$this->week_number}";
    }
}
