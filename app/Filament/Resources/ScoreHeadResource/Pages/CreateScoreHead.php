<?php

namespace App\Filament\Resources\ScoreHeadResource\Pages;

use App\Filament\Resources\ScoreHeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScoreHead extends CreateRecord
{
    protected static string $resource = ScoreHeadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
