<?php

namespace App\Filament\Resources\TeacherSubjectAssignmentResource\Pages;

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacherSubjectAssignment extends CreateRecord
{
    protected static string $resource = TeacherSubjectAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
