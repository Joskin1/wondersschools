<?php

namespace App\Filament\Sudo\Resources\SchoolResource\Pages;

use App\Filament\Sudo\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchools extends ManageRecords
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New School'),
        ];
    }
}
