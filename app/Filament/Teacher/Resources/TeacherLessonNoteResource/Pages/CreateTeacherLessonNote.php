<?php

namespace App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource;
use App\Jobs\ProcessLessonNoteUpload;
use App\Models\LessonNote;
use App\Models\Session;
use App\Models\TeacherSubjectAssignment;
use App\Models\ClassTeacherAssignment;
use App\Services\DocumentMarkdownParserService;
use App\Services\LessonDocxParserService;
use App\Services\LessonNoteCache;
use App\Services\LessonSubmissionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateTeacherLessonNote extends CreateRecord
{
    protected static string $resource = TeacherLessonNoteResource::class;

    protected ?string $heading = 'Create Lesson Note';

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('teacher.template.lesson-note'))
                ->openUrlInNewTab(),
        ];
    }

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

        // Inject session context & draft status
        $data['teacher_id'] = auth()->id();
        $data['session_id'] = $activeSession->id;
        $data['term_id'] = $activeTerm->id;
        $data['status'] = 'draft';

        // Validate teacher assignment - check class teacher first, then subject teacher
        $isClassTeacher = ClassTeacherAssignment::isClassTeacher(
            auth()->id(),
            $data['classroom_id'],
            $activeSession->id
        );

        $isSubjectTeacher = TeacherSubjectAssignment::isAssigned(
            auth()->id(),
            $data['subject_id'],
            $data['classroom_id'],
            $activeSession->id,
            $activeTerm->id
        );

        if (!$isClassTeacher && !$isSubjectTeacher) {
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
                ->title('Lesson Note Already Exists')
                ->body('You have already created a lesson note for this combination. Please edit the existing one.')
                ->warning()
                ->send();
            $this->halt();
        }

        $this->submissionType = $data['submission_type'] ?? 'written';

        if (in_array($this->submissionType, ['file', 'template'], true)) {
            $templateFile = $data['template_file'] ?? null;
            if (!$templateFile) {
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
            } else {
                $sourceDisk = 'local';
                if (!Storage::disk($sourceDisk)->exists($templateFile)) {
                    if (Storage::disk('public')->exists($templateFile)) {
                        $sourceDisk = 'public';
                    } elseif (Storage::disk('lesson_notes')->exists($templateFile)) {
                        $sourceDisk = 'lesson_notes';
                    }
                }

                $fileContents = Storage::disk($sourceDisk)->get($templateFile);
                $originalName = basename($templateFile);
                $fileSize = Storage::disk($sourceDisk)->size($templateFile);
                $mimeType = Storage::disk($sourceDisk)->mimeType($templateFile) ?: 'application/octet-stream';
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            }

            $fileHash = hash('sha256', $fileContents);

            // Build permanent storage path on lesson_notes disk
            $permanentPath = \App\Models\LessonNoteVersion::buildStoragePath(
                $activeSession->id,
                $activeTerm->id,
                (int) $data['week_number'],
                auth()->id(),
                $fileHash,
                $extension ?: 'bin'
            );

            // Store permanently on lesson_notes disk
            Storage::disk('lesson_notes')->put($permanentPath, $fileContents);

            // Clean up temporary upload if on local disk
            if ($sourceDisk === 'local' && Storage::disk('local')->exists($templateFile)) {
                Storage::disk('local')->delete($templateFile);
            }

            // Record metadata for afterCreate
            $this->uploadedFilePath = $permanentPath;
            $this->uploadedFileName = $originalName;
            $this->uploadedOriginalName = $originalName;
            $this->uploadedFileSize = $fileSize;
            $this->uploadedFileHash = $fileHash;
            $this->uploadedMimeType = $mimeType;

            // Optional title or default to filename
            $rawTitle = !empty($data['title']) ? $data['title'] : pathinfo($originalName, PATHINFO_FILENAME);
            $this->writtenTitle = mb_strtoupper(trim(str_replace(['_', '-'], ' ', $rawTitle)));
        } else {
            $this->writtenTitle = !empty($data['title']) ? mb_strtoupper(trim($data['title'])) : null;
            $this->writtenContent = $data['content'] ?? null;
            $this->writtenImages = $data['images'] ?? null;
        }

        if (!empty($this->writtenTitle)) {
            $this->writtenTitle = mb_strtoupper(trim($this->writtenTitle));
        }

        if (isset($data['learning_objectives']) && is_array($data['learning_objectives'])) {
            $data['learning_objectives'] = collect($data['learning_objectives'])
                ->map(fn ($obj) => is_array($obj) ? ($obj['objective'] ?? '') : $obj)
                ->filter()
                ->values()
                ->toArray();
        }

        unset($data['submission_type'], $data['template_file'], $data['title'], $data['content'], $data['images'], $data['draft_manager']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (in_array($this->submissionType, ['file', 'template'], true)) {
            $version = \App\Models\LessonNoteVersion::create([
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
            $version = \App\Models\LessonNoteVersion::create([
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
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Lesson note saved as draft. When ready, submit both Lesson Plan and Lesson Note from the Lesson Plans page.';
    }
}
