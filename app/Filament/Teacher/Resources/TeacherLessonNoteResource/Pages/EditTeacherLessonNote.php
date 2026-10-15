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
    private ?string $uploadedFilePath = null;
    private ?string $uploadedFileName = null;
    private ?string $uploadedOriginalName = null;
    private ?int $uploadedFileSize = null;
    private ?string $uploadedFileHash = null;
    private ?string $uploadedMimeType = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $version = $this->record->latestVersion;

        if ($version?->isFile()) {
            $data['submission_type'] = 'file';
            $data['title'] = $version->title;
            $data['template_file'] = $version->file_path;
        } else {
            $data['submission_type'] = 'written';
            $data['title'] = $version?->title;
            $data['content'] = $version?->content;
            $data['images'] = $version?->images;
        }

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
        $currentVersion = $this->record->latestVersion;

        if (in_array($this->submissionType, ['file', 'template'], true)) {
            $templateFile = $data['template_file'] ?? null;

            if (!$templateFile && (!$currentVersion || empty($currentVersion->file_path))) {
                Notification::make()
                    ->title('Document file required')
                    ->body('Please upload your document file (.pdf, .docx, .doc, .ppt, .pptx, images, etc.).')
                    ->danger()
                    ->send();
                $this->halt();
            }

            if ($templateFile instanceof \Illuminate\Http\UploadedFile) {
                $fileContents = file_get_contents($templateFile->getRealPath());
                $originalName = $templateFile->getClientOriginalName();
                $fileSize = $templateFile->getSize();
                $mimeType = $templateFile->getMimeType() ?: 'application/octet-stream';
                $extension = strtolower($templateFile->getClientOriginalExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION));
                $fileHash = hash('sha256', $fileContents);

                $permanentPath = LessonNoteVersion::buildStoragePath(
                    $this->record->session_id,
                    $this->record->term_id,
                    (int) $this->record->week_number,
                    auth()->id(),
                    $fileHash,
                    $extension ?: 'bin'
                );

                \Illuminate\Support\Facades\Storage::disk('lesson_notes')->put($permanentPath, $fileContents);

                $this->uploadedFilePath = $permanentPath;
                $this->uploadedFileName = $originalName;
                $this->uploadedOriginalName = $originalName;
                $this->uploadedFileSize = $fileSize;
                $this->uploadedFileHash = $fileHash;
                $this->uploadedMimeType = $mimeType;
            } elseif (is_string($templateFile) && $templateFile !== $currentVersion?->file_path) {
                $sourceDisk = 'local';
                if (!\Illuminate\Support\Facades\Storage::disk($sourceDisk)->exists($templateFile)) {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($templateFile)) {
                        $sourceDisk = 'public';
                    } elseif (\Illuminate\Support\Facades\Storage::disk('lesson_notes')->exists($templateFile)) {
                        $sourceDisk = 'lesson_notes';
                    }
                }

                $fileContents = \Illuminate\Support\Facades\Storage::disk($sourceDisk)->get($templateFile);
                $originalName = basename($templateFile);
                $fileSize = \Illuminate\Support\Facades\Storage::disk($sourceDisk)->size($templateFile);
                $mimeType = \Illuminate\Support\Facades\Storage::disk($sourceDisk)->mimeType($templateFile) ?: 'application/octet-stream';
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $fileHash = hash('sha256', $fileContents);

                $permanentPath = LessonNoteVersion::buildStoragePath(
                    $this->record->session_id,
                    $this->record->term_id,
                    (int) $this->record->week_number,
                    auth()->id(),
                    $fileHash,
                    $extension ?: 'bin'
                );

                \Illuminate\Support\Facades\Storage::disk('lesson_notes')->put($permanentPath, $fileContents);

                $this->uploadedFilePath = $permanentPath;
                $this->uploadedFileName = $originalName;
                $this->uploadedOriginalName = $originalName;
                $this->uploadedFileSize = $fileSize;
                $this->uploadedFileHash = $fileHash;
                $this->uploadedMimeType = $mimeType;
            } else {
                // Kept existing file
                $this->uploadedFilePath = $currentVersion?->file_path;
                $this->uploadedFileName = $currentVersion?->file_name;
                $this->uploadedOriginalName = $currentVersion?->original_filename ?: $currentVersion?->file_name;
                $this->uploadedFileSize = $currentVersion?->file_size;
                $this->uploadedFileHash = $currentVersion?->file_hash;
                $this->uploadedMimeType = $currentVersion?->mime_type;
            }

            $rawTitle = !empty($data['title']) ? $data['title'] : ($this->uploadedFileName ? pathinfo($this->uploadedFileName, PATHINFO_FILENAME) : 'LESSON NOTE');
            $this->writtenTitle = mb_strtoupper(trim(str_replace(['_', '-'], ' ', $rawTitle)));
        } else {
            $this->writtenTitle = !empty($data['title']) ? mb_strtoupper(trim($data['title'])) : null;
            $this->writtenContent = $data['content'] ?? null;
            $this->writtenImages = $data['images'] ?? null;
        }

        if (!empty($this->writtenTitle)) {
            $this->writtenTitle = mb_strtoupper(trim($this->writtenTitle));
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
        if (in_array($this->submissionType, ['file', 'template'], true)) {
            $version = LessonNoteVersion::create([
                'lesson_note_id' => $this->record->id,
                'submission_type' => 'file',
                'title' => $this->writtenTitle,
                'learning_objectives' => $this->record->learning_objectives,
                'file_path' => $this->uploadedFilePath,
                'file_name' => $this->uploadedFileName,
                'original_filename' => $this->uploadedOriginalName,
                'file_size' => $this->uploadedFileSize,
                'file_hash' => $this->uploadedFileHash,
                'mime_type' => $this->uploadedMimeType,
                'uploaded_by' => auth()->id(),
                'status' => 'draft',
            ]);
        } else {
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
        }

        $this->record->update([
            'latest_version_id' => $version->id,
            'status' => 'draft',
        ]);

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
