<?php

namespace App\Filament\Resources\ClassTeacherAssignmentResource\Pages;

use App\Filament\Resources\ClassTeacherAssignmentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;

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

    protected function onValidationError(\Illuminate\Validation\ValidationException $exception): void
    {
        // Check if it's a unique constraint violation
        if ($exception->getPrevious() instanceof QueryException) {
            $queryException = $exception->getPrevious();
            
            if (str_contains($queryException->getMessage(), 'unique_class_teacher_per_session')) {
                Notification::make()
                    ->title('Duplicate Assignment')
                    ->body('This class already has a class teacher for the selected session.')
                    ->danger()
                    ->send();
                
                $this->halt();
            }
        }

        parent::onValidationError($exception);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
