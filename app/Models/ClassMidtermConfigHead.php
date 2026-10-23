<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassMidtermConfigHead extends Model
{
    protected $fillable = [
        'class_midterm_config_id',
        'score_head_id',
        'max_score_override',
    ];

    protected $casts = [
        'max_score_override' => 'integer',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(ClassMidtermConfig::class, 'class_midterm_config_id');
    }

    public function scoreHead(): BelongsTo
    {
        return $this->belongsTo(ScoreHead::class);
    }

    public function getEffectiveMaxScoreAttribute(): int
    {
        return $this->max_score_override ?? $this->scoreHead->max_score;
    }
}
