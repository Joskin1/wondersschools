<?php

namespace App\Filament\Resources\ScoreHeadResource\Pages;

use App\Filament\Resources\ScoreHeadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScoreHeads extends ListRecords
{
    protected static string $resource = ScoreHeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
