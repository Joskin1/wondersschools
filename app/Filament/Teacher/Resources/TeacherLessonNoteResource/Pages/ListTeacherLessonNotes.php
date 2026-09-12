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
            Actions\Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('teacher.template.lesson-note'))
                ->openUrlInNewTab(),

            Actions\CreateAction::make()->label('Submit Lesson Note'),
        ];
    }
}
