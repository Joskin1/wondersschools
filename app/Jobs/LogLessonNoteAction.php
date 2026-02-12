<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\LessonNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogLessonNoteAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $lessonNoteId,
        public string $action,
        public int $userId,
        public string $details
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            AuditLog::create([
                'auditable_type' => LessonNote::class,
                'auditable_id' => $this->lessonNoteId,
                'action' => $this->action,
                'user_id' => $this->userId,
                'details' => $this->details,
                'ip_address' => request()->ip() ?? 'N/A',
            ]);

            Log::channel('single')->info('Lesson Note Action', [
                'lesson_note_id' => $this->lessonNoteId,
                'action' => $this->action,
                'user_id' => $this->userId,
                'details' => $this->details,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log lesson note action", [
                'lesson_note_id' => $this->lessonNoteId,
                'action' => $this->action,
                'error' => $e->getMessage(),
            ]);

            // Don't throw - audit logging failures shouldn't break the main flow
        }
    }
}
