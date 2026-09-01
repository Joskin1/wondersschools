<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy_registration_link')
                ->label('Copy Registration Link')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->visible(fn (Student $record) => ! $record->is_portal_active)
                ->modalHeading('Registration Link Information')
                ->modalContent(function (Student $record) {
                    $rawToken = $record->createRegistrationLink();
                    $url = route('student.register', [
                        'slug' => $record->registration_slug,
                        'token' => $rawToken,
                    ]);
                    $expiresAt = $record->registration_expires_at->format('M d, Y H:i');

                    return view('filament.modals.generated-registration-link', [
                        'url' => $url,
                        'expiresAt' => $expiresAt,
                        'studentId' => $record->id,
                        'note' => 'A fresh link was generated. It expires in 3 days and can only be used once.',
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('lg'),
        ];
    }
}
