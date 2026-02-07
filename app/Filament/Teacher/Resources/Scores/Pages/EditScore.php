<?php

namespace App\Filament\Teacher\Resources\Scores\Pages;

use App\Filament\Teacher\Resources\Scores\ScoreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScore extends EditRecord
{
    protected static string $resource = ScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
