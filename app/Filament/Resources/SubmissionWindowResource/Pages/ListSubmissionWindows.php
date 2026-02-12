<?php

namespace App\Filament\Resources\SubmissionWindowResource\Pages;

use App\Filament\Resources\SubmissionWindowResource;
use Filament\Resources\Pages\ListRecords;

class ListSubmissionWindows extends ListRecords
{
    protected static string $resource = SubmissionWindowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
