<?php

namespace App\Services;

use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\User;
use App\Notifications\LessonSubmissionReady;
use Illuminate\Support\Facades\Log;

class LessonSubmissionService
{
    /**
     * Check if both Lesson Note and Lesson Plan are ready for admin review.
     * If so, notify admins (only once, when the second document becomes ready).
     */
    public function checkAndNotifyIfComplete(LessonNote|LessonPlan $trigger): void
    {
        if ($trigger instanceof LessonNote) {
            $note = $trigger;
            $plan = $note->getPairedLessonPlan();
        } else {
            $plan = $trigger;
            $note = LessonNote::where('teacher_id', $plan->teacher_id)
                ->where('subject_id', $plan->subject_id)
                ->where('classroom_id', $plan->classroom_id)
                ->where('session_id', $plan->session_id)
                ->where('term_id', $plan->term_id)
                ->where('week_number', $plan->week_number)
                ->first();
        }

        // Both must exist and be pending (ready for review)
        if (
            $note && $plan &&
            in_array($note->status, ['pending']) &&
            in_array($plan->status, ['pending'])
        ) {
            $this->notifyAdmins($note, $plan);
        }
    }

    /**
     * Submit both Lesson Plan and paired Lesson Note for review.
     */
    public function submitPairForReview(LessonPlan $plan): array
    {
        $note = LessonNote::where('teacher_id', $plan->teacher_id)
            ->where('subject_id', $plan->subject_id)
            ->where('classroom_id', $plan->classroom_id)
            ->where('session_id', $plan->session_id)
            ->where('term_id', $plan->term_id)
            ->where('week_number', $plan->week_number)
            ->first();

        if (!$note) {
            return [
                'success' => false,
                'message' => "No Lesson Note found for Week {$plan->week_number}. Please create and save your Lesson Note for this week before submitting for review.",
            ];
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($plan, $note) {
            $plan->update(['status' => 'pending']);
            $note->update(['status' => 'pending']);
            if ($note->latestVersion) {
                $note->latestVersion->update(['status' => 'pending']);
            }
        });

        $this->notifyAdmins($note, $plan);

        return [
            'success' => true,
            'message' => "Lesson Plan and Lesson Note for Week {$plan->week_number} have been submitted for admin review.",
        ];
    }

    /**
     * Check if a lesson submission pair is complete and ready for review.
     */
    public function isPairComplete(LessonNote $note): bool
    {
        $plan = $note->getPairedLessonPlan();

        return $plan !== null
            && in_array($note->status, ['pending', 'approved'])
            && in_array($plan->status, ['pending', 'approved']);
    }

    /**
     * Get the submission status summary for a given LessonNote context.
     */
    public function getSubmissionStatus(int $teacherId, int $subjectId, int $classroomId, int $sessionId, int $termId, int $weekNumber): array
    {
        $note = LessonNote::where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->where('week_number', $weekNumber)
            ->first();

        $plan = LessonPlan::where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->where('week_number', $weekNumber)
            ->first();

        $isComplete = $note && $plan
            && in_array($note->status, ['pending', 'approved'])
            && in_array($plan->status, ['pending', 'approved']);

        return [
            'lesson_note' => $note,
            'lesson_plan' => $plan,
            'note_status' => $note?->status ?? 'not_started',
            'plan_status' => $plan?->status ?? 'not_started',
            'has_note' => $note !== null,
            'has_plan' => $plan !== null,
            'is_complete' => $isComplete,
            'missing_note' => !$note || !in_array($note->status, ['pending', 'approved']),
            'missing_plan' => !$plan || !in_array($plan->status, ['pending', 'approved']),
        ];
    }

    protected function notifyAdmins(LessonNote $note, LessonPlan $plan): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'sudo'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new LessonSubmissionReady($note, $plan));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admins of lesson submission: ' . $e->getMessage());
        }
    }
}
