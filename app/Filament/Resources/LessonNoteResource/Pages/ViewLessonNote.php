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

    public function getTitle(): string
    {
        return 'Review Lesson Submission';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load(['latestVersion', 'teacher', 'subject', 'classroom', 'session', 'term']);

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
            Actions\Action::make('download_note_pdf')
                ->label('Download Note PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('admin.lesson-note.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('download_plan_pdf')
                ->label('Download Plan PDF')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(fn () => $this->record->getPairedLessonPlan() ? route('admin.lesson-plan.pdf', $this->record->getPairedLessonPlan()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->getPairedLessonPlan() !== null),

            Actions\Action::make('download')
                ->label('Download File')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => $this->record->latestVersion?->getDownloadUrl() ? ($this->record->latestVersion->getDownloadUrl() . '?download=1') : '#')
                ->openUrlInNewTab()
                ->color('gray')
                ->visible(fn () => $this->record->latestVersion !== null
                    && $this->record->latestVersion->isFile()
                    && !empty($this->record->latestVersion->file_path)),

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
                    // approve() on LessonNote cascades to paired LessonPlan in DB transaction
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
                        'Approved by ' . auth()->user()->full_name
                    );

                    Notification::make()
                        ->title('Submission Approved')
                        ->body('The teacher has been notified. The lesson submission is now approved.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'
                    && $this->record->getPairedLessonPlan()?->status === 'pending'),

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
                    // reject() on LessonNote cascades to LessonPlan
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
                        'Rejected by ' . auth()->user()->full_name
                    );

                    Notification::make()
                        ->title('Submission Rejected')
                        ->body('The teacher has been notified.')
                        ->warning()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'
                    && $this->record->getPairedLessonPlan()?->status === 'pending'),

            Actions\ActionGroup::make([
                Actions\Action::make('delete_note')
                    ->label('Delete Lesson Note')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Lesson Note?')
                    ->modalDescription('Are you sure you want to delete this Lesson Note? The teacher will be able to start again and create a new lesson note for this week. This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete lesson note')
                    ->action(function () {
                        $record = $this->record;
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            $pairedPlan = $record->getPairedLessonPlan();
                            if ($pairedPlan && in_array($pairedPlan->status, ['pending', 'approved', 'rejected'])) {
                                $pairedPlan->update(['status' => 'draft']);
                            }

                            foreach ($record->versions as $version) {
                                if ($version->file_path) {
                                    try {
                                        \Illuminate\Support\Facades\Storage::disk('lesson_notes')->delete($version->file_path);
                                    } catch (\Throwable $e) {}
                                }
                            }
                            $record->versions()->forceDelete();
                            $record->forceDelete();
                        });

                        Notification::make()
                            ->title('Lesson Note Deleted')
                            ->body('The lesson note has been permanently deleted. The teacher can now create a new lesson note for this week.')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    })
                    ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'sudo'], true)),

                Actions\Action::make('delete_plan')
                    ->label('Delete Lesson Plan')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Paired Lesson Plan?')
                    ->modalDescription('Are you sure you want to delete the paired Lesson Plan? The teacher will be able to start again and create a new lesson plan for this week. This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete lesson plan')
                    ->visible(fn () => $this->record->getPairedLessonPlan() !== null
                        && in_array(auth()->user()?->role, ['admin', 'sudo'], true))
                    ->action(function () {
                        $record = $this->record;
                        $plan = $record->getPairedLessonPlan();
                        if ($plan) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($plan, $record) {
                                $plan->referenceMaterials()->detach();
                                $plan->instructionalMaterials()->detach();
                                $plan->teachingMethods()->detach();
                                $plan->forceDelete();

                                $record->update(['status' => 'draft']);
                                if ($record->latestVersion) {
                                    $record->latestVersion->update(['status' => 'draft']);
                                }
                            });

                            Notification::make()
                                ->title('Lesson Plan Deleted')
                                ->body('The paired lesson plan has been deleted. The teacher can now create a new lesson plan for this week.')
                                ->success()
                                ->send();

                            $this->redirect($this->getResource()::getUrl('index'));
                        }
                    }),

                Actions\Action::make('delete_both')
                    ->label('Delete Both (Note & Plan)')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Both Lesson Note and Lesson Plan?')
                    ->modalDescription('Are you sure you want to delete both this Lesson Note and its paired Lesson Plan? The teacher will be able to start both again from scratch for this week. This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete both')
                    ->visible(fn () => $this->record->getPairedLessonPlan() !== null
                        && in_array(auth()->user()?->role, ['admin', 'sudo'], true))
                    ->action(function () {
                        $record = $this->record;
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            $plan = $record->getPairedLessonPlan();
                            if ($plan) {
                                $plan->referenceMaterials()->detach();
                                $plan->instructionalMaterials()->detach();
                                $plan->teachingMethods()->detach();
                                $plan->forceDelete();
                            }

                            foreach ($record->versions as $version) {
                                if ($version->file_path) {
                                    try {
                                        \Illuminate\Support\Facades\Storage::disk('lesson_notes')->delete($version->file_path);
                                    } catch (\Throwable $e) {}
                                }
                            }
                            $record->versions()->forceDelete();
                            $record->forceDelete();
                        });

                        Notification::make()
                            ->title('Lesson Note and Plan Deleted')
                            ->body('Both documents have been permanently deleted. The teacher can now start both from scratch.')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    }),
            ])
                ->label('Delete Options')
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }
}
