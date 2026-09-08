<?php

namespace App\Notifications;

use App\Models\LessonNote;
use App\Models\LessonPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LessonSubmissionReady extends Notification
{
    use Queueable;

    public function __construct(
        public LessonNote $lessonNote,
        public LessonPlan $lessonPlan
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $teacher = $this->lessonNote->teacher;
        $subject = $this->lessonNote->subject;
        $classroom = $this->lessonNote->classroom;
        $week = $this->lessonNote->week_number;

        return [
            'title' => 'Lesson Submission Ready for Review',
            'body' => "Complete lesson submission from {$teacher?->name} — {$subject?->name} · {$classroom?->name} · Week {$week}. "
                . "Both Lesson Note and Lesson Plan are ready for review.",
            'lesson_note_id' => $this->lessonNote->id,
            'lesson_plan_id' => $this->lessonPlan->id,
            'teacher_name' => $teacher?->name,
            'subject_name' => $subject?->name,
            'classroom_name' => $classroom?->name,
            'week_number' => $week,
            'actions' => [
                [
                    'name' => 'review',
                    'label' => 'Review Submission',
                    'url' => "/admin/lesson-notes/{$this->lessonNote->id}",
                ],
            ],
        ];
    }
}
