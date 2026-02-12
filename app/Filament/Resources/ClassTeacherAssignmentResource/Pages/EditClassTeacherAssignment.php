<?php

namespace App\Filament\Resources\ClassTeacherAssignmentResource\Pages;

use App\Filament\Resources\ClassTeacherAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
