<?php

namespace App\Filament\Student\Resources\StudentAssignmentResource\Pages;

use App\Filament\Student\Resources\StudentAssignmentResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentAssignments extends ListRecords
{
    protected static string $resource = StudentAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for students
        ];
    }
}
