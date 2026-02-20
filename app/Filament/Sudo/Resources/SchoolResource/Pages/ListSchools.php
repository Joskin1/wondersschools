<?php

namespace App\Filament\Sudo\Resources\SchoolResource\Pages;

use App\Filament\Sudo\Resources\SchoolResource;
use Filament\Resources\Pages\ListRecords;

class ListSchools extends ListRecords
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
