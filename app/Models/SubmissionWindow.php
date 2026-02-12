<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SubmissionWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'term_id',
        'week_number',
        'opens_at',
        'closes_at',
        'is_open',
        'updated_by',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'is_open' => 'boolean',
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
     * Get the user who last updated this window.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get currently open windows.
     */
    public function scopeCurrentlyOpen($query)
    {
        $now = Carbon::now();
        
        return $query->where('is_open', true)
            ->where('opens_at', '<=', $now)
            ->where('closes_at', '>=', $now);
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
     * Check if this window is currently open.
     */
    public function isCurrentlyOpen(): bool
    {
        $now = Carbon::now();
        
        return $this->is_open 
            && $this->opens_at <= $now 
            && $this->closes_at >= $now;
    }

    /**
     * Open this window.
     */
    public function open(?int $userId = null): void
    {
        $this->update([
            'is_open' => true,
            'updated_by' => $userId,
        ]);
    }

    /**
     * Close this window.
     */
    public function close(?int $userId = null): void
    {
        $this->update([
            'is_open' => false,
            'updated_by' => $userId,
        ]);
    }

    /**
     * Get the cache key for this window.
     */
    public function getCacheKey(): string
    {
        return "submission_window:{$this->session_id}:{$this->term_id}:{$this->week_number}";
    }
}
