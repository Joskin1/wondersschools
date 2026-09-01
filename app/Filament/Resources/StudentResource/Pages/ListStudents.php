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
            Action::make('copy_student_registration_link')
                ->label('Copy Registration Link')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->modalHeading('Public Student Registration Link')
                ->modalDescription('Share this link with parents or prospective students. Anyone using this link can complete full student registration online.')
                ->modalContent(function () {
                    $link = url('/register/student');
                    return view('filament.components.copy-student-link-modal', ['link' => $link]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

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
