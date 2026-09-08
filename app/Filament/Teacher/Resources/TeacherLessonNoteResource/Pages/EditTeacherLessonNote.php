<?php

namespace App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource;
use App\Jobs\ProcessLessonNoteUpload;
use App\Models\LessonNoteVersion;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTeacherLessonNote extends EditRecord
{
    protected static string $resource = TeacherLessonNoteResource::class;

    private string $submissionType = 'file';
    private ?string $writtenTitle = null;
    private ?string $writtenContent = null;
    private ?array $writtenImages = null;
    private ?string $uploadedFilePath = null;
    private ?string $originalFilePath = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $version = $this->record->latestVersion;
        $this->originalFilePath = $version?->file_path;

        $data['submission_type'] = $version?->isWritten() ? 'written' : 'file';
        $data['title'] = $version?->title;
        $data['content'] = $version?->content;
        $data['images'] = $version?->images;
        $data['file'] = $version?->isFile() ? $version->file_path : null;
        $data['learning_objectives'] = collect($this->record->learning_objectives ?? [])
            ->map(fn ($objective) => ['objective' => is_array($objective) ? ($objective['objective'] ?? '') : $objective])
            ->values()
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! $this->record->canBeEditedByTeacher()) {
            Notification::make()
                ->title('Lesson note is locked')
                ->body('This lesson note is already attached to a submitted lesson plan and is pending admin review.')
                ->danger()
                ->send();
            $this->halt();
        }

        $this->submissionType = $data['submission_type'] ?? 'file';
        $this->uploadedFilePath = $data['file'] ?? null;
        $this->writtenTitle = $data['title'] ?? null;
        $this->writtenContent = $data['content'] ?? null;
        $this->writtenImages = $data['images'] ?? null;

        $data['learning_objectives'] = collect($data['learning_objectives'] ?? [])
            ->pluck('objective')
            ->filter()
            ->values()
            ->toArray();
        $data['status'] = 'pending';

        unset($data['submission_type'], $data['file'], $data['title'], $data['content'], $data['images']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->submissionType === 'written') {
            $version = LessonNoteVersion::create([
                'lesson_note_id' => $this->record->id,
                'submission_type' => 'written',
                'title' => $this->writtenTitle,
                'learning_objectives' => $this->record->learning_objectives,
                'content' => $this->writtenContent,
                'images' => $this->writtenImages,
                'file_name' => ($this->writtenTitle ?: 'Written Lesson Note') . ' (Week ' . $this->record->week_number . ')',
                'file_size' => strlen($this->writtenContent ?? ''),
                'file_hash' => hash('sha256', ($this->writtenContent ?? '') . json_encode($this->writtenImages ?? [])),
                'uploaded_by' => auth()->id(),
                'mime_type' => 'text/html',
                'status' => 'pending',
            ]);

            $this->record->update(['latest_version_id' => $version->id]);
        } elseif ($this->uploadedFilePath && $this->uploadedFilePath !== $this->originalFilePath) {
            ProcessLessonNoteUpload::dispatch(
                $this->record->id,
                $this->uploadedFilePath,
                basename($this->uploadedFilePath),
                auth()->id()
            );
        }

        Notification::make()
            ->title('Lesson note updated')
            ->body('Your corrected lesson note has been submitted again for review.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
