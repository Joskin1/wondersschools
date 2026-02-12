<?php

namespace App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource;
use App\Jobs\ProcessLessonNoteUpload;
use App\Models\LessonNote;
use App\Models\Session;
use App\Models\TeacherSubjectAssignment;
use App\Services\LessonNoteCache;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacherLessonNote extends CreateRecord
{
    protected static string $resource = TeacherLessonNoteResource::class;

    protected ?string $heading = 'Upload Lesson Note';

    private ?string $uploadedFilePath = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        if (!$activeSession || !$activeTerm) {
            Notification::make()
                ->title('No Active Session')
                ->body('There is no active academic session or term.')
                ->danger()
                ->send();
            $this->halt();
        }

        // Inject session context
        $data['teacher_id'] = auth()->id();
        $data['session_id'] = $activeSession->id;
        $data['term_id'] = $activeTerm->id;
        $data['status'] = 'pending';

        // Validate submission window is open
        $cache = app(LessonNoteCache::class);
        $window = $cache->getActiveWindow(
            $activeSession->id,
            $activeTerm->id,
            $data['week_number']
        );

        if (!$window) {
            Notification::make()
                ->title('Submission Window Closed')
                ->body('The submission window for this week is not currently open.')
                ->danger()
                ->send();
            $this->halt();
        }

        // Validate teacher assignment
        $isAssigned = TeacherSubjectAssignment::isAssigned(
            auth()->id(),
            $data['subject_id'],
            $data['classroom_id'],
            $activeSession->id,
            $activeTerm->id
        );

        if (!$isAssigned) {
            Notification::make()
                ->title('Not Assigned')
                ->body('You are not assigned to this subject/class combination.')
                ->danger()
                ->send();
            $this->halt();
        }

        // Check for duplicate submission
        $existing = LessonNote::where('teacher_id', auth()->id())
            ->where('subject_id', $data['subject_id'])
            ->where('classroom_id', $data['classroom_id'])
            ->where('session_id', $activeSession->id)
            ->where('term_id', $activeTerm->id)
            ->where('week_number', $data['week_number'])
            ->first();

        if ($existing) {
            Notification::make()
                ->title('Already Submitted')
                ->body('You have already submitted a lesson note for this combination. Use the re-upload option to submit a new version.')
                ->warning()
                ->send();
            $this->halt();
        }

        // Store file path and remove from data (not a DB column)
        $this->uploadedFilePath = $data['file'] ?? null;
        unset($data['file']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (!$this->uploadedFilePath) {
            return;
        }

        $fileName = basename($this->uploadedFilePath);

        ProcessLessonNoteUpload::dispatch(
            $this->record->id,
            $this->uploadedFilePath,
            $fileName,
            auth()->id()
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Lesson note uploaded successfully. It is now being processed.';
    }
}
