<?php

namespace App\Filament\Resources\AcademicSessionResource\Pages;

use App\Filament\Resources\AcademicSessionResource;
use Filament\Resources\Pages\EditRecord;

class EditSession extends EditRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
        ];
    }
}
