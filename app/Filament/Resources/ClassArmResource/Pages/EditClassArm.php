<?php

namespace App\Filament\Resources\ClassArmResource\Pages;

use App\Filament\Resources\ClassArmResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassArm extends EditRecord
{
    protected static string $resource = ClassArmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign_students')
                ->label('Assign Students')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false)
                ->modalHeading(fn (): string => "Assign Students to {$this->record->full_name}")
                ->modalDescription('Select one or more students from this classroom to add them to this arm at once.')
                ->modalWidth('4xl')
                ->modalSubmitActionLabel('Assign Selected Students')
                ->form(fn (): array => ClassArmResource::assignStudentsFormSchema($this->record))
                ->action(fn (array $data) => ClassArmResource::handleAssignStudents($data, $this->record)),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
