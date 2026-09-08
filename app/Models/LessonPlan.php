<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'classroom_id',
        'session_id',
        'term_id',
        'week_number',
        'status',
        'title',
        'time',
        'section',
        'learning_objectives',
        'key_vocabulary',
        'prior_knowledge',
        'content',
        'presentation_steps',
        'strategies_activities',
        'evaluation_questions',
        'conclusion',
        'assignment',
        'admin_comment',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'week_number' => 'integer',
        'learning_objectives' => 'array',
        'presentation_steps' => 'array',
        'evaluation_questions' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function referenceMaterials(): BelongsToMany
    {
        return $this->belongsToMany(ReferenceMaterial::class, 'lesson_plan_reference_materials');
    }

    public function instructionalMaterials(): BelongsToMany
    {
        return $this->belongsToMany(InstructionalMaterial::class, 'lesson_plan_instructional_materials');
    }

    public function teachingMethods(): BelongsToMany
    {
        return $this->belongsToMany(TeachingMethod::class, 'lesson_plan_teaching_methods');
    }

    /**
     * Find the paired Lesson Note for the same academic context.
     */
    public function getPairedLessonNote(): ?LessonNote
    {
        return LessonNote::where('teacher_id', $this->teacher_id)
            ->where('subject_id', $this->subject_id)
            ->where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('week_number', $this->week_number)
            ->first();
    }

    public function scopeForWeek($query, int $weekNumber)
    {
        return $query->where('week_number', $weekNumber);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForClassroom($query, int $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeActive($query)
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        if (!$activeSession || !$activeTerm) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('session_id', $activeSession->id)
            ->where('term_id', $activeTerm->id);
    }

    public function approve(?string $comment = null, ?int $reviewerId = null): void
    {
        $this->update([
            'status' => 'approved',
            'admin_comment' => $comment,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(?string $comment = null, ?int $reviewerId = null): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_comment' => $comment,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }
}
