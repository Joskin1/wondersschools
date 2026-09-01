<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $isSudo = auth()->user()?->isSudo();

        return [
            DeleteAction::make()->visible(fn () => $isSudo),
            RestoreAction::make()->visible(fn () => $isSudo),
            ForceDeleteAction::make()->visible(fn () => $isSudo),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
