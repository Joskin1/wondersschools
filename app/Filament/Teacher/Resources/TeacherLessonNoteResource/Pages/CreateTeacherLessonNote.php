<?php

namespace App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource;
use App\Jobs\ProcessLessonNoteUpload;
use App\Models\LessonNote;
use App\Models\Session;
use App\Models\TeacherSubjectAssignment;
use App\Models\ClassTeacherAssignment;
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

    private ?string $templateFilePath = null;
    private string $submissionType = 'written';
    private ?string $writtenTitle = null;
    private ?string $writtenContent = null;
    private ?array $writtenImages = null;

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

            $fullPath = Storage::disk('public')->path($templateFile);
            $parser = app(LessonDocxParserService::class);
            $validation = $parser->validateLessonNoteTemplate($fullPath);

            if (!$validation['valid']) {
                Storage::disk('public')->delete($templateFile);
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
                Storage::disk('public')->delete($templateFile);
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

        $this->record->update([
            'latest_version_id' => $version->id,
        ]);

        if (!empty($this->templateFilePath)) {
            Storage::disk('public')->delete($this->templateFilePath);
        }
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
