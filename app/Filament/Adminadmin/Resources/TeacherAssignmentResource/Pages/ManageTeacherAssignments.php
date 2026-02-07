<?php

namespace App\Filament\Adminadmin\Resources\TeacherAssignmentResource\Pages;

use App\Filament\Adminadmin\Resources\TeacherAssignmentResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTeacherAssignments extends ManageRecords
{
    protected static string $resource = TeacherAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions are defined in the resource's headerActions
        ];
    }
}
