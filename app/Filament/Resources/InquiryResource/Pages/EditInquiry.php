<?php

namespace App\Filament\Resources\InquiryResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\InquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInquiry extends EditRecord
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
