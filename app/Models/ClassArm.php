<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassArm extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent classroom this arm belongs to.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get enrollments in this arm.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'class_arm_id');
    }

    /**
     * Get class teacher assignments for this arm.
     */
    public function classTeacherAssignments(): HasMany
    {
        return $this->hasMany(ClassTeacherAssignment::class, 'class_arm_id');
    }

    /**
     * Scope to active arms.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Full combined name (e.g., "JSS 1 — Gold").
     */
    public function getFullNameAttribute(): string
    {
        $classroomName = $this->classroom?->name;
        if ($classroomName) {
            return "{$classroomName} — {$this->name}";
        }

        return $this->name;
    }
}
