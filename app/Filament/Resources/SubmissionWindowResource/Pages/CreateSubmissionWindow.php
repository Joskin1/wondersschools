<?php

namespace App\Filament\Resources\SubmissionWindowResource\Pages;

use App\Filament\Resources\SubmissionWindowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubmissionWindow extends CreateRecord
{
    protected static string $resource = SubmissionWindowResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $exists = \App\Models\SubmissionWindow::where('session_id', $data['session_id'])
            ->where('term_id', $data['term_id'])
            ->where('week_number', $data['week_number'])
            ->exists();

        if ($exists) {
            \Filament\Notifications\Notification::make()
                ->title('Duplicate Submission Window')
                ->body("A submission window for Week {$data['week_number']} in the selected session and term already exists.")
                ->danger()
                ->send();
            $this->halt();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
