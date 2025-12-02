<?php

namespace App\Filament\Resources\EvaluationSettingResource\Pages;

use App\Filament\Resources\EvaluationSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvaluationSetting extends EditRecord
{
    protected static string $resource = EvaluationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
