<?php

namespace App\Filament\Resources\AssessmentTypes\Pages;

use App\Filament\Resources\AssessmentTypes\AssessmentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentType extends EditRecord
{
    protected static string $resource = AssessmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
