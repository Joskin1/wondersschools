<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScoreHeader extends Model
{
    protected $fillable = [
        'name',
        'max_score',
        'school_class_id',
        'session',
        'term',
        'display_order',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'term' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Get the classroom this score header belongs to
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'school_class_id');
    }

    /**
     * Get all scores for this header
     */
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    /**
     * Get score headers for a class, session, and term
     */
    public static function getHeaders(int $classroomId, string $session, int $term): array
    {
        return static::where('school_class_id', $classroomId)
            ->where('session', $session)
            ->where('term', $term)
            ->orderBy('display_order')
            ->get()
            ->toArray();
    }

    /**
     * Calculate total max score for all headers
     */
    public static function getTotalMaxScore(int $classroomId, string $session, int $term): float
    {
        return static::where('school_class_id', $classroomId)
            ->where('session', $session)
            ->where('term', $term)
            ->sum('max_score');
    }
}

