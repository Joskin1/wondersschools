<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Auto-generate a subject code from the name if not provided.
     * Prevents NOT NULL violation on the code column.
     */
    protected static function booted(): void
    {
        static::creating(function (Subject $subject) {
            if (empty($subject->code)) {
                $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $subject->name), 0, 10));
                if (empty($baseCode)) {
                    $baseCode = 'SUBJ';
                }
                $code = $baseCode;
                $suffix = 1;
                while (static::withTrashed()->where('code', $code)->exists()) {
                    $code = substr($baseCode, 0, 7) . $suffix;
                    $suffix++;
                }
                $subject->code = $code;
            }
        });
    }

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
