<?php

namespace App\Filament\Resources\TeacherSubjectAssignmentResource\Pages;

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherSubjectAssignments extends ListRecords
{
    protected static string $resource = TeacherSubjectAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
