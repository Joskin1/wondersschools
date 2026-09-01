<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy_teacher_registration_link')
                ->label('Copy Teacher Registration Link')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->modalHeading('Teacher Self-Registration Link')
                ->modalDescription('Share this link with teachers in your staff group. Anyone using this link can register their teacher profile. Portal access will remain pending until you activate it.')
                ->modalContent(function () {
                    $link = route('public.teacher.register');
                    return view('filament.components.copy-teacher-link-modal', ['link' => $link]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            CreateAction::make(),
        ];
    }
}
