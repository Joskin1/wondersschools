<?php

namespace App\Filament\Resources\ClassTeacherAssignmentResource\Pages;

use App\Filament\Resources\ClassTeacherAssignmentResource;
use Filament\Resources\Pages\ListRecords;

class ListClassTeacherAssignments extends ListRecords
{
    protected static string $resource = ClassTeacherAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
