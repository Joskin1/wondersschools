<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'class_order',
        'is_active',
    ];

    protected $casts = [
        'class_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to filter only active classrooms.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by academic level (class_order ascending).
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('class_order');
    }

    /**
     * Get all lesson notes for this classroom.
     */
    public function lessonNotes(): HasMany
    {
        return $this->hasMany(LessonNote::class);
    }

    /**
     * Get all teacher subject assignments for this classroom.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }

    /**
     * Get all class teacher assignments for this classroom.
     */
    public function classTeacherAssignments(): HasMany
    {
        return $this->hasMany(ClassTeacherAssignment::class, 'class_id');
    }
}
