<?php

namespace App\Filament\Resources\TeacherSubjectAssignmentResource\Pages;

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class EditTeacherSubjectAssignment extends EditRecord
{
    protected static string $resource = TeacherSubjectAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            $record->update($data);

            return $record;
        } catch (UniqueConstraintViolationException $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'unique_subject_per_class')) {
                Notification::make()
                    ->title('Subject Already Assigned')
                    ->body('This subject is already assigned to another teacher in this class for the selected term.')
                    ->danger()
                    ->send();
            } elseif (str_contains($message, 'unique_assignment')) {
                Notification::make()
                    ->title('Duplicate Assignment')
                    ->body('This exact assignment already exists.')
                    ->danger()
                    ->send();
            }

            $this->halt();
        }
    }
}
