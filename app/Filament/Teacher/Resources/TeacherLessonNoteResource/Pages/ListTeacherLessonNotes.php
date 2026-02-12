<?php

namespace App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherLessonNotes extends ListRecords
{
    protected static string $resource = TeacherLessonNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Upload Lesson Note'),
        ];
    }
}
