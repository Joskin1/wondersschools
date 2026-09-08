<?php

namespace App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonPlanResource;
use App\Models\LessonPlan;
use App\Services\LessonSubmissionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTeacherLessonPlan extends EditRecord
{
    protected static string $resource = TeacherLessonPlanResource::class;

    protected ?string $heading = 'Edit Lesson Plan';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Re-wrap repeater arrays so the UI can display them properly
        if (isset($data['learning_objectives']) && is_array($data['learning_objectives'])) {
            $data['learning_objectives'] = collect($data['learning_objectives'])
                ->map(fn ($obj) => is_string($obj) ? ['objective' => $obj] : $obj)
                ->toArray();
        }

        if (isset($data['presentation_steps']) && is_array($data['presentation_steps'])) {
            $data['presentation_steps'] = collect($data['presentation_steps'])
                ->map(fn ($s) => is_string($s) ? ['step' => $s] : $s)
                ->toArray();
        }

        if (isset($data['evaluation_questions']) && is_array($data['evaluation_questions'])) {
            $data['evaluation_questions'] = collect($data['evaluation_questions'])
                ->map(fn ($q) => is_string($q) ? ['question' => $q] : $q)
                ->toArray();
        }

        // TagsInput expects an array
        if (isset($data['key_vocabulary']) && is_string($data['key_vocabulary'])) {
            $data['key_vocabulary'] = array_map('trim', explode(',', $data['key_vocabulary']));
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Normalize repeater arrays back to flat
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

        if (isset($data['key_vocabulary']) && is_array($data['key_vocabulary'])) {
            $data['key_vocabulary'] = implode(', ', $data['key_vocabulary']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit_for_review')
                ->label('Submit for Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Submit Lesson Plan for Admin Review?')
                ->modalDescription('Once submitted, you will not be able to edit this lesson plan unless it is rejected.')
                ->visible(fn () => in_array($this->record->status, ['draft', 'rejected']))
                ->action(function () {
                    $this->record->update(['status' => 'pending']);

                    // Check if paired submission is complete and notify admin
                    app(LessonSubmissionService::class)->checkAndNotifyIfComplete($this->record);

                    Notification::make()
                        ->title('Lesson Plan Submitted')
                        ->body('Your lesson plan has been submitted for admin review.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Lesson plan updated.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
