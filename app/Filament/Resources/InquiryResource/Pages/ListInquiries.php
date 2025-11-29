<?php

namespace App\Filament\Resources\InquiryResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\InquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInquiries extends ListRecords
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
