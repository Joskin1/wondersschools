<?php

namespace App\Filament\Resources\LessonNoteResource\Pages;

use App\Filament\Resources\LessonNoteResource;
use App\Jobs\LogLessonNoteAction;
use App\Notifications\LessonNoteReviewed;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewLessonNote extends ViewRecord
{
    protected static string $resource = LessonNoteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure latestVersion is loaded
        $this->record->load('latestVersion');
        
        // Explicitly set the admin_comment in the form data
        if ($this->record->latestVersion) {
            $data['latestVersion'] = [
                'admin_comment' => $this->record->latestVersion->admin_comment,
            ];
        }
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download File')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => $this->record->latestVersion?->getDownloadUrl())
                ->openUrlInNewTab()
                ->color('primary')
                ->visible(fn () => $this->record->latestVersion !== null),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('comment')
                        ->label('Comment (Optional)')
                        ->rows(3)
                        ->placeholder('Great work! Approved.')
                        ->helperText('This comment will be visible to the teacher'),
                ])
                ->action(function (array $data) {
                    $this->record->approve($data['comment'] ?? null, auth()->id());

                    $this->record->teacher->notify(new LessonNoteReviewed(
                        $this->record->load(['subject', 'classroom']),
                        'approved',
                        $data['comment'] ?? null
                    ));

                    LogLessonNoteAction::dispatch(
                        $this->record->id,
                        'approve',
                        auth()->id(),
                        'Approved by ' . auth()->user()->name
                    );

                    Notification::make()
                        ->title('Lesson Note Approved')
                        ->body('The teacher has been notified.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('comment')
                        ->label('Reason for Rejection')
                        ->required()
                        ->rows(3)
                        ->placeholder('Please revise and resubmit...')
                        ->helperText('This comment will be visible to the teacher'),
                ])
                ->action(function (array $data) {
                    $this->record->reject($data['comment'], auth()->id());

                    $this->record->teacher->notify(new LessonNoteReviewed(
                        $this->record->load(['subject', 'classroom']),
                        'rejected',
                        $data['comment']
                    ));

                    LogLessonNoteAction::dispatch(
                        $this->record->id,
                        'reject',
                        auth()->id(),
                        'Rejected by ' . auth()->user()->name
                    );

                    Notification::make()
                        ->title('Lesson Note Rejected')
                        ->body('The teacher has been notified.')
                        ->warning()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }
}
