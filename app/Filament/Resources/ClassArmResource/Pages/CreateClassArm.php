<?php

namespace App\Filament\Resources\ClassArmResource\Pages;

use App\Filament\Resources\ClassArmResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassArm extends CreateRecord
{
    protected static string $resource = ClassArmResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
