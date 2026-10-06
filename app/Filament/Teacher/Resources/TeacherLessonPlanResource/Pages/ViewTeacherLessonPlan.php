<?php

namespace App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonPlanResource;
use App\Models\LessonPlan;
use App\Services\LessonSubmissionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacherLessonPlan extends ViewRecord
{
    protected static string $resource = TeacherLessonPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->url(fn () => $this->getResource()::getUrl('edit', ['record' => $this->record]))
                ->visible(fn () => in_array($this->record->status, ['draft', 'rejected'])),

            Action::make('submit_for_review')
                ->label('Submit for Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Submit for Admin Review?')
                ->modalDescription('Both this Lesson Plan and the corresponding Lesson Note for this week will be submitted for admin review.')
                ->visible(fn () => in_array($this->record->status, ['draft', 'rejected']))
                ->action(function () {
                    $result = app(LessonSubmissionService::class)->submitPairForReview($this->record);

                    if (!$result['success']) {
                        Notification::make()
                            ->title('Cannot Submit for Review')
                            ->body($result['message'])
                            ->danger()
                            ->persistent()
                            ->send();
                        return;
                    }

                    Notification::make()
                        ->title('Submitted for Review')
                        ->body($result['message'])
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Re-wrap repeater arrays for view rendering
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

        if (isset($data['key_vocabulary']) && is_string($data['key_vocabulary'])) {
            $data['key_vocabulary'] = array_map('trim', explode(',', $data['key_vocabulary']));
        }

        return $data;
    }
}
