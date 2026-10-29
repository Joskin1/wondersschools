<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassMidtermConfig extends Model
{
    protected $fillable = [
        'classroom_id',
        'midterm_weight',
        'calculation_method',
        'is_locked',
    ];

    protected $casts = [
        'midterm_weight' => 'integer',
        'is_locked'      => 'boolean',
    ];

    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function heads(): HasMany
    {
        return $this->hasMany(ClassMidtermConfigHead::class);
    }

    /**
     * Derived exam weight: 100 - midterm_weight.
     */
    public function getExamWeightAttribute(): int
    {
        return 100 - (int) $this->midterm_weight;
    }
}
