<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScoreHead extends Model
{
    protected $fillable = ['name', 'max_score', 'is_active', 'created_by'];

    protected $casts = [
        'max_score' => 'integer',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function structureItems(): HasMany
    {
        return $this->hasMany(ClassScoreStructureItem::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
