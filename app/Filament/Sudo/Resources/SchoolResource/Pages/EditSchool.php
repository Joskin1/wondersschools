<?php

namespace App\Filament\Sudo\Resources\SchoolResource\Pages;

use App\Filament\Sudo\Resources\SchoolResource;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
