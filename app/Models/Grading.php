<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grading extends Model
{
    protected $fillable = [
        'letter',
        'lower_bound',
        'upper_bound',
        'remark',
        'subject_id',
        'session',
    ];

    protected $casts = [
        'lower_bound' => 'decimal:2',
        'upper_bound' => 'decimal:2',
    ];

    /**
     * Get the subject this grading belongs to
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get grade for a given score
     */
    public static function getGradeForScore(
        float $score,
        ?int $subjectId = null,
        ?string $session = null
    ): ?self {
        $query = static::where('lower_bound', '<=', $score)
            ->where('upper_bound', '>=', $score);

        // Try subject-specific grading first
        if ($subjectId) {
            $grading = (clone $query)
                ->where('subject_id', $subjectId)
                ->when($session, fn($q) => $q->where('session', $session))
                ->first();
            
            if ($grading) {
                return $grading;
            }
        }

        // Fall back to global grading
        return $query->whereNull('subject_id')
            ->when($session, fn($q) => $q->where('session', $session))
            ->orWhereNull('session')
            ->first();
    }

    /**
     * Get all gradings for a subject or global
     */
    public static function getGradings(?int $subjectId = null, ?string $session = null): array
    {
        $query = static::query();

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        } else {
            $query->whereNull('subject_id');
        }

        if ($session) {
            $query->where(fn($q) => $q->where('session', $session)->orWhereNull('session'));
        }

        return $query->orderBy('lower_bound', 'desc')->get()->toArray();
    }
}

