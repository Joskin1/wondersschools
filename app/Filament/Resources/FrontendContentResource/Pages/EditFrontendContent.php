<?php

namespace App\Filament\Resources\FrontendContentResource\Pages;

use App\Filament\Resources\FrontendContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFrontendContent extends EditRecord
{
    protected static string $resource = FrontendContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
