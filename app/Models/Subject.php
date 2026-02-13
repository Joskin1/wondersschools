<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all lesson notes for this subject.
     */
    public function lessonNotes(): HasMany
    {
        return $this->hasMany(LessonNote::class);
    }

    /**
     * Get all teachers assigned to this subject.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_subject_assignments', 'subject_id', 'teacher_id')
            ->withPivot(['classroom_id', 'session_id', 'term_id'])
            ->withTimestamps();
    }

    /**
     * Get all teacher assignments for this subject.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }

    /**
     * Get all classrooms this subject is assigned to.
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class);
    }

    /**
     * Scope to filter only active subjects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
