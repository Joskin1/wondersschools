<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassScoreStructure extends Model
{
    protected $fillable = ['class_id', 'session_id', 'term_id', 'total_score', 'locked'];

    protected $casts = [
        'total_score' => 'integer',
        'locked'      => 'boolean',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClassScoreStructureItem::class);
    }

    /**
     * Recompute and persist the total_score cache from current items.
     */
    public function recalculateTotal(): void
    {
        $total = $this->items()
            ->join('score_heads', 'score_heads.id', '=', 'class_score_structure_items.score_head_id')
            ->selectRaw('SUM(COALESCE(class_score_structure_items.max_score_override, score_heads.max_score)) as total')
            ->value('total') ?? 0;

        $this->update(['total_score' => (int) $total]);
    }

    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForTerm($query, int $termId)
    {
        return $query->where('term_id', $termId);
    }
}
