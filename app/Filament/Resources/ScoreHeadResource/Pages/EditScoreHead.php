<?php

namespace App\Filament\Resources\ScoreHeadResource\Pages;

use App\Filament\Resources\ScoreHeadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScoreHead extends EditRecord
{
    protected static string $resource = ScoreHeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }
}
