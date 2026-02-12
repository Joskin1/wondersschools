<?php

namespace App\Filament\Resources\SubmissionWindowResource\Pages;

use App\Filament\Resources\SubmissionWindowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubmissionWindow extends CreateRecord
{
    protected static string $resource = SubmissionWindowResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
