<?php

namespace App\Filament\Resources\ScoreResource\Pages;

use App\Filament\Resources\ScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScores extends ListRecords
{
    protected static string $resource = ScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulkInput')
                ->label('Bulk Score Input')
                ->icon('heroicon-o-table-cells')
                ->url(fn (): string => ScoreResource::getUrl('bulk-input')),
            Actions\CreateAction::make(),
        ];
    }
}
