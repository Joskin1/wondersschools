<?php

namespace App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacherLessonNote extends ViewRecord
{
    protected static string $resource = TeacherLessonNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download File')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => $this->record->latestVersion?->getDownloadUrl())
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->latestVersion !== null),
        ];
    }
}
