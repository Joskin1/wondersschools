<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'section',
    ];

    /**
     * Get all lesson notes for this classroom.
     */
    public function lessonNotes(): HasMany
    {
        return $this->hasMany(LessonNote::class);
    }

    /**
     * Get all teacher assignments for this classroom.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }
}
