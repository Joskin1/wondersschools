<?php

namespace App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonPlanResource;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\Session;
use App\Models\TeacherSubjectAssignment;
use App\Services\LessonSubmissionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacherLessonPlan extends CreateRecord
{
    protected static string $resource = TeacherLessonPlanResource::class;

    protected ?string $heading = 'Create Lesson Plan';

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

        // Validate teacher is assigned to this subject/classroom
        $isAssigned = TeacherSubjectAssignment::isAssigned(
            auth()->id(),
            $data['subject_id'],
            $data['classroom_id'],
            $activeSession->id,
            $activeTerm->id,
        );

        if (!$isAssigned) {
            Notification::make()
                ->title('Not Assigned')
                ->body('You are not assigned to this subject/class combination.')
                ->danger()
                ->send();
            $this->halt();
        }

        // Check for duplicate
        $existing = LessonPlan::where('teacher_id', auth()->id())
            ->where('subject_id', $data['subject_id'])
            ->where('classroom_id', $data['classroom_id'])
            ->where('session_id', $activeSession->id)
            ->where('term_id', $activeTerm->id)
            ->where('week_number', $data['week_number'])
            ->first();

        if ($existing) {
            Notification::make()
                ->title('Lesson Plan Already Exists')
                ->body('You have already created a lesson plan for this combination. Please edit the existing one.')
                ->warning()
                ->send();
            $this->halt();
        }

        $data['teacher_id'] = auth()->id();
        $data['session_id'] = $activeSession->id;
        $data['term_id'] = $activeTerm->id;
        $data['status'] = 'draft';

        // Normalize repeater arrays
        if (isset($data['learning_objectives'])) {
            $data['learning_objectives'] = collect($data['learning_objectives'])
                ->pluck('objective')
                ->filter()
                ->values()
                ->toArray();
        }

        if (isset($data['presentation_steps'])) {
            $data['presentation_steps'] = collect($data['presentation_steps'])
                ->pluck('step')
                ->filter()
                ->values()
                ->toArray();
        }

        if (isset($data['evaluation_questions'])) {
            $data['evaluation_questions'] = collect($data['evaluation_questions'])
                ->pluck('question')
                ->filter()
                ->values()
                ->toArray();
        }

        // key_vocabulary comes in as array from TagsInput
        if (isset($data['key_vocabulary']) && is_array($data['key_vocabulary'])) {
            $data['key_vocabulary'] = implode(', ', $data['key_vocabulary']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Sync pivot relationships
        if (isset($this->data['reference_materials'])) {
            $record->referenceMaterials()->sync($this->data['reference_materials']);
        }
        if (isset($this->data['instructional_materials'])) {
            $record->instructionalMaterials()->sync($this->data['instructional_materials']);
        }
        if (isset($this->data['teaching_methods'])) {
            $record->teachingMethods()->sync($this->data['teaching_methods']);
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Lesson plan saved as draft. When ready, submit it for review.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
