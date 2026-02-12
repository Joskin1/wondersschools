<?php

namespace App\Jobs;

use App\Models\LessonNote;
use App\Models\User;
use App\Notifications\LessonNoteSubmitted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class NotifyAdminOfNewSubmission implements ShouldQueue
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
        public int $lessonNoteId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $lessonNote = LessonNote::with(['teacher', 'subject', 'classroom', 'session', 'term'])
                ->findOrFail($this->lessonNoteId);

            // Get all admin users
            $admins = User::whereIn('role', ['admin', 'sudo'])->get();

            if ($admins->isEmpty()) {
                Log::warning("No admin users found to notify about lesson note submission", [
                    'lesson_note_id' => $this->lessonNoteId,
                ]);
                return;
            }

            Notification::send($admins, new LessonNoteSubmitted($lessonNote));

        } catch (\Exception $e) {
            Log::error("Failed to notify admins of lesson note submission", [
                'lesson_note_id' => $this->lessonNoteId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
