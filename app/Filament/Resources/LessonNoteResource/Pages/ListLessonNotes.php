<?php

namespace App\Filament\Resources\LessonNoteResource\Pages;

use App\Filament\Resources\LessonNoteResource;
use Filament\Resources\Pages\ListRecords;

class ListLessonNotes extends ListRecords
{
    protected static string $resource = LessonNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - teachers create via their own resource
        ];
    }
}
