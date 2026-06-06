<?php

namespace App\Filament\Student\Resources\LessonNoteResource\Pages;

use App\Filament\Student\Resources\LessonNoteResource;
use Filament\Resources\Pages\ListRecords;

class ListLessonNotes extends ListRecords
{
    protected static string $resource = LessonNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Students cannot create lesson notes
        ];
    }
}
