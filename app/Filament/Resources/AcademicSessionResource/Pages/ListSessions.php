<?php

namespace App\Filament\Resources\AcademicSessionResource\Pages;

use App\Filament\Resources\AcademicSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListSessions extends ListRecords
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
