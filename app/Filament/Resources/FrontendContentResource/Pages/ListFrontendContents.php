<?php

namespace App\Filament\Resources\FrontendContentResource\Pages;

use App\Filament\Resources\FrontendContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFrontendContents extends ListRecords
{
    protected static string $resource = FrontendContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
