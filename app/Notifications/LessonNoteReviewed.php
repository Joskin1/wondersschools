<?php

namespace App\Notifications;

use App\Models\LessonNote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LessonNoteReviewed extends Notification
{
    use Queueable;

    public function __construct(
        public LessonNote $lessonNote,
        public string $status,
        public ?string $comment = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $verb = $this->status === 'approved' ? 'approved' : 'rejected';

        $body = "Your lesson note for {$this->lessonNote->subject->name} - "
            . "{$this->lessonNote->classroom->name} (Week {$this->lessonNote->week_number}) "
            . "has been {$verb}.";

        if ($this->comment) {
            $body .= " Feedback: {$this->comment}";
        }

        return [
            'title' => 'Lesson Note ' . ucfirst($verb),
            'body' => $body,
            'lesson_note_id' => $this->lessonNote->id,
            'status' => $this->status,
        ];
    }
}
