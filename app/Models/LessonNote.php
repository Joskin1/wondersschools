<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'classroom_id',
        'session_id',
        'term_id',
        'week_number',
        'latest_version_id',
        'status',
    ];

    protected $casts = [
        'week_number' => 'integer',
    ];

    /**
     * Get the teacher who created this lesson note.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the subject for this lesson note.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the classroom for this lesson note.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the session for this lesson note.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the term for this lesson note.
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get all versions of this lesson note.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(LessonNoteVersion::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest version of this lesson note.
     */
    public function latestVersion(): BelongsTo
    {
        return $this->belongsTo(LessonNoteVersion::class, 'latest_version_id');
    }

    /**
     * Scope to filter by week.
     */
    public function scopeForWeek($query, int $weekNumber)
    {
        return $query->where('week_number', $weekNumber);
    }

    /**
     * Scope to filter by subject.
     */
    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope to filter by classroom.
     */
    public function scopeForClassroom($query, int $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    /**
     * Scope to filter by teacher.
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope to get pending lesson notes.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved lesson notes.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected lesson notes.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to filter by active session and term.
     */
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

    /**
     * Approve this lesson note.
     */
    public function approve(?string $comment = null, ?int $reviewerId = null): void
    {
        $this->update(['status' => 'approved']);

        if ($this->latestVersion) {
            $this->latestVersion->update([
                'status' => 'approved',
                'admin_comment' => $comment,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);
        }
    }

    /**
     * Reject this lesson note.
     */
    public function reject(?string $comment = null, ?int $reviewerId = null): void
    {
        $this->update(['status' => 'rejected']);

        if ($this->latestVersion) {
            $this->latestVersion->update([
                'status' => 'rejected',
                'admin_comment' => $comment,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);
        }
    }

    /**
     * Check if this lesson note can be edited by the teacher.
     */
    public function canBeEditedByTeacher(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Generate signed URL for direct-to-storage uploads.
     */
    public static function generateUploadSignedUrl(
        int $sessionId,
        int $termId,
        int $weekNumber,
        int $teacherId,
        string $filename
    ): string {
        $path = "lesson-notes/{$sessionId}/{$termId}/week-{$weekNumber}/{$teacherId}/{$filename}";
        $expiresAt = now()->addMinutes(30); // 30 minutes for upload
        
        $signature = hash_hmac('sha256', $path . '|' . $expiresAt->timestamp, config('app.key'));
        
        return url('/api/upload/lesson-notes?path=' . urlencode($path) . '&expires=' . $expiresAt->timestamp . '&signature=' . $signature);
    }

    /**
     * Attempt file upload with error handling.
     */
    public function attemptFileUpload(string $filename, string $content): bool
    {
        return \Illuminate\Support\Facades\Storage::disk('lesson_notes')->put($filename, $content);
    }

    /**
     * Upload with fallback to alternative storage.
     */
    public function uploadWithFallback(string $content, string $filename, array $disks = ['s3', 'backup_storage']): bool
    {
        foreach ($disks as $disk) {
            try {
                $result = \Illuminate\Support\Facades\Storage::disk($disk)->put("lesson-notes/{$filename}", $content);
                // Storage::put returns true/false for faked storage, or path string for real storage
                if ($result !== false) {
                    return true;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return false;
    }

    /**
     * Safe upload with graceful error handling.
     */
    public function safeUpload(string $filename, string $content): array
    {
        try {
            $success = $this->attemptFileUpload($filename, $content);
            return [
                'success' => $success,
                'error' => null,
                'fallback_used' => false,
            ];
        } catch (\Exception $e) {
            // Try fallback
            $fallbackSuccess = $this->uploadWithFallback($content, $filename);
            return [
                'success' => $fallbackSuccess,
                'error' => $e->getMessage(),
                'fallback_used' => true,
            ];
        }
    }

    /**
     * Notify admin synchronously (fallback when queue is down).
     */
    public function notifyAdminSync(): void
    {
        try {
            $admins = \App\Models\User::whereIn('role', ['admin', 'sudo'])->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\LessonNoteSubmitted($this));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to notify admin: ' . $e->getMessage());
        }
    }

    /**
     * Check if there are changes between two versions.
     */
    public function hasVersionChanges(int $version1Id, int $version2Id): bool
    {
        $v1 = $this->versions()->find($version1Id);
        $v2 = $this->versions()->find($version2Id);
        
        if (!$v1 || !$v2) {
            return false;
        }
        
        return $v1->file_hash !== $v2->file_hash;
    }
}

