<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => ImportStudents::downloadTemplate()),

            Action::make('bulk_import')
                ->label('Bulk Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(StudentResource::getUrl('import')),

            CreateAction::make(),
        ];
    }
}
