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

    private string $submissionType = 'written';
    private ?string $writtenTitle = null;
    private ?string $writtenContent = null;
    private ?array $writtenImages = null;
    private ?string $templateFilePath = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $version = $this->record->latestVersion;

        $data['submission_type'] = 'written';
        $data['title'] = $version?->title;
        $data['content'] = $version?->content;
        $data['images'] = $version?->images;
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
                ->body('This lesson note is currently pending admin review.')
                ->danger()
                ->send();
            $this->halt();
        }

        $this->submissionType = $data['submission_type'] ?? 'written';

        if ($this->submissionType === 'template') {
            $templateFile = $data['template_file'] ?? null;
            if (!$templateFile) {
                Notification::make()
                    ->title('Template file required')
                    ->body('Please upload your completed .docx template.')
                    ->danger()
                    ->send();
                $this->halt();
            }

            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($templateFile);
            $parser = app(\App\Services\LessonDocxParserService::class);
            $validation = $parser->validateLessonNoteTemplate($fullPath);

            if (!$validation['valid']) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($templateFile);
                Notification::make()
                    ->title('Invalid Template Format')
                    ->body(implode(' ', $validation['errors']))
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
            }

            try {
                $parsed = $parser->parseLessonNote($fullPath);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($templateFile);
                Notification::make()
                    ->title('Failed to read template')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
                $this->halt();
            }

            $this->writtenTitle = $parsed['title'] ?: 'Lesson Note';
            $this->writtenContent = $parsed['content'] ?: '';
            if (!empty($parsed['learning_objectives'])) {
                $data['learning_objectives'] = $parsed['learning_objectives'];
            }
            $this->templateFilePath = $templateFile;
        } else {
            $this->writtenTitle = $data['title'] ?? null;
            $this->writtenContent = $data['content'] ?? null;
            $this->writtenImages = $data['images'] ?? null;
        }

        $data['learning_objectives'] = collect($data['learning_objectives'] ?? [])
            ->map(fn ($obj) => is_array($obj) ? ($obj['objective'] ?? '') : $obj)
            ->filter()
            ->values()
            ->toArray();
        $data['status'] = 'draft';

        unset($data['submission_type'], $data['template_file'], $data['title'], $data['content'], $data['images'], $data['draft_manager']);

        return $data;
    }

    protected function afterSave(): void
    {
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
            'status' => 'draft',
        ]);

        $this->record->update([
            'latest_version_id' => $version->id,
            'status' => 'draft',
        ]);

        if (!empty($this->templateFilePath)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->templateFilePath);
        }

        Notification::make()
            ->title('Lesson note updated')
            ->body('Your lesson note has been updated and saved as draft.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
