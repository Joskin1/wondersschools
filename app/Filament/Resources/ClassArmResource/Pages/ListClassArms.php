<?php

namespace App\Filament\Resources\ClassArmResource\Pages;

use App\Filament\Resources\ClassArmResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassArms extends ListRecords
{
    protected static string $resource = ClassArmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Class Arm')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
