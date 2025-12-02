<?php

namespace App\Filament\Resources\EvaluationSettingResource\Pages;

use App\Filament\Resources\EvaluationSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvaluationSettings extends ListRecords
{
    protected static string $resource = EvaluationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
