<?php

namespace App\Notifications;

use App\Models\LessonNote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LessonNoteSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        public LessonNote $lessonNote
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Lesson Note Submitted',
            'body' => "{$this->lessonNote->teacher->name} submitted a lesson note for "
                . "{$this->lessonNote->subject->name} - {$this->lessonNote->classroom->name} "
                . "(Week {$this->lessonNote->week_number})",
            'lesson_note_id' => $this->lessonNote->id,
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'Review Now',
                    'url' => "/admin/lesson-notes/{$this->lessonNote->id}",
                ],
            ],
        ];
    }
}
