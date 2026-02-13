<?php

namespace App\Filament\Resources\ClassTeacherAssignmentResource\Pages;

use App\Filament\Resources\ClassTeacherAssignmentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class EditClassTeacherAssignment extends EditRecord
{
    protected static string $resource = ClassTeacherAssignmentResource::class;

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

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Class teacher assignment updated successfully.';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            $record->update($data);

            return $record;
        } catch (UniqueConstraintViolationException $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'unique_teacher_per_session')) {
                Notification::make()
                    ->title('Teacher Already Assigned')
                    ->body('This teacher is already assigned as a class teacher for another class in the selected session.')
                    ->danger()
                    ->send();
            } elseif (str_contains($message, 'unique_class_teacher_per_session')) {
                Notification::make()
                    ->title('Duplicate Assignment')
                    ->body('This class already has a class teacher for the selected session.')
                    ->danger()
                    ->send();
            }

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
