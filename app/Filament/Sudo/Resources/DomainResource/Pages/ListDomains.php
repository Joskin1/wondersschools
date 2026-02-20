<?php

namespace App\Filament\Sudo\Resources\DomainResource\Pages;

use App\Filament\Sudo\Resources\DomainResource;
use Filament\Resources\Pages\ListRecords;

class ListDomains extends ListRecords
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
