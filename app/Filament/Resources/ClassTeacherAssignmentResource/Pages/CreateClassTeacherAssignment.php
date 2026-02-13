<?php

namespace App\Filament\Resources\ClassTeacherAssignmentResource\Pages;

use App\Filament\Resources\ClassTeacherAssignmentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateClassTeacherAssignment extends CreateRecord
{
    protected static string $resource = ClassTeacherAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Class teacher assigned successfully.';
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return static::getModel()::create($data);
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
