<?php

namespace App\Filament\Resources\ClassArmResource\Pages;

use App\Filament\Resources\ClassArmResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassArms extends ListRecords
{
    protected static string $resource = ClassArmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign_students_to_arm')
                ->label('Assign Students to Arm')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false)
                ->modalHeading('Bulk Assign Students to Class Arm')
                ->modalDescription('Select a classroom, choose the target arm, and check multiple students to add them all at once.')
                ->modalWidth('4xl')
                ->modalSubmitActionLabel('Assign Selected Students')
                ->form(fn (): array => ClassArmResource::assignStudentsFormSchema())
                ->action(fn (array $data) => ClassArmResource::handleAssignStudents($data)),

            Actions\CreateAction::make()
                ->label('New Class Arm')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
