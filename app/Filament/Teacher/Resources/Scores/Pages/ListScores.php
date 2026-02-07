<?php

namespace App\Filament\Teacher\Resources\Scores\Pages;

use App\Filament\Teacher\Resources\Scores\ScoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScores extends ListRecords
{
    protected static string $resource = ScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
