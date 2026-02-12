<?php

namespace App\Filament\Resources\SubmissionWindowResource\Pages;

use App\Filament\Resources\SubmissionWindowResource;
use App\Services\LessonNoteCache;
use Filament\Resources\Pages\EditRecord;

class EditSubmissionWindow extends EditRecord
{
    protected static string $resource = SubmissionWindowResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function afterSave(): void
    {
        // Invalidate cache when window is updated
        app(LessonNoteCache::class)->invalidateWindow(
            $this->record->session_id,
            $this->record->term_id,
            $this->record->week_number
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
