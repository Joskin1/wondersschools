<?php

namespace App\Filament\Resources\ClassGroupResource\Pages;

use App\Filament\Resources\ClassGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassGroup extends CreateRecord
{
    protected static string $resource = ClassGroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
