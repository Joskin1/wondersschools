<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassScoreStructureItem extends Model
{
    protected $fillable = ['class_score_structure_id', 'score_head_id', 'max_score_override'];

    protected $casts = [
        'max_score_override' => 'integer',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(ClassScoreStructure::class, 'class_score_structure_id');
    }

    public function scoreHead(): BelongsTo
    {
        return $this->belongsTo(ScoreHead::class);
    }

    /**
     * The effective max score: override takes priority over the ScoreHead default.
     */
    public function getEffectiveMaxScoreAttribute(): int
    {
        return $this->max_score_override ?? $this->scoreHead->max_score;
    }
}
