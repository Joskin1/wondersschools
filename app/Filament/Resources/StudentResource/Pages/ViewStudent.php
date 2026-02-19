<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_registration_link')
                ->label('Generate Registration Link')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->visible(fn (Student $record) => $record->isPending() && !$record->registration_slug)
                ->requiresConfirmation()
                ->modalHeading('Generate Registration Link')
                ->modalDescription('This will create a unique registration link for the student. The link will expire in 3 days.')
                ->action(function (Student $record) {
                    $rawToken = $record->createRegistrationLink();
                    $url = route('student.register', [
                        'slug' => $record->registration_slug,
                        'token' => $rawToken,
                    ]);

                    Notification::make()
                        ->title('Registration Link Generated')
                        ->body("**Copy this link and send it to the student/parent:**\n\n{$url}\n\n⚠️ This link will expire in 3 days.")
                        ->success()
                        ->persistent()
                        ->send();

                    // Refresh the page to show the new link status
                    redirect()->to(request()->url());
                }),

            Action::make('view_registration_link')
                ->label('View Registration Link')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (Student $record) => $record->isPending() && $record->registration_slug && !$record->hasExpiredRegistration())
                ->modalHeading('Registration Link Information')
                ->modalContent(function (Student $record) {
                    $url = route('student.register', ['slug' => $record->registration_slug]);
                    $expiresAt = $record->registration_expires_at->format('M d, Y H:i');
                    
                    return view('filament.modals.registration-link-info', [
                        'url' => $url,
                        'expiresAt' => $expiresAt,
                        'note' => 'The full link with token was sent when the link was first generated. You can only view the base URL here for security reasons.',
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }
}
