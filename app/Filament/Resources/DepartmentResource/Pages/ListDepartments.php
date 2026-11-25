<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DepartmentResource::makeAssignStudentsAction(
                name: 'assign_students_to_department',
                label: 'Assign Students to Department',
            ),

            CreateAction::make(),
        ];
    }
}
