<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                        'admissionNumber' => $record->admission_number,
                        'note' => 'A fresh link was generated. It expires in 3 days and can only be used once.',
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('lg'),

            Action::make('reset_biometrics')
                ->label('Reset Biometrics')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn (Student $record) => $record->isBiometricEnrolled())
                ->requiresConfirmation()
                ->modalHeading('Reset Biometric Data')
                ->modalDescription(fn (Student $record) => "Are you sure you want to clear biometric templates for {$record->full_name}? The student will need to be re-scanned on a biometric enrollment station.")
                ->action(function (Student $record) {
                    $record->clearBiometrics();
                    Notification::make()
                        ->title('Biometric Data Cleared')
                        ->body("Biometric templates for {$record->full_name} have been cleared.")
                        ->warning()
                        ->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $uploadDisk = config('filesystems.upload_disk', 'public');

        return $schema
            ->schema([
                Section::make('Student Information')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(3)->schema([
                            ImageEntry::make('profile_picture')
                                ->label('')
                                ->circular()
                                ->size(80)
                                ->disk($uploadDisk)
                                ->defaultImageUrl(fn (Student $student): string => 'https://ui-avatars.com/api/?name=' . urlencode($student->full_name) . '&background=6366f1&color=fff&size=80'),

                            TextEntry::make('full_name')
                                ->label('Full Name')
                                ->weight('bold')
                                ->size('lg'),

                            TextEntry::make('admission_number')
                                ->label('Admission Number')
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('current_classroom')
                                ->label('Classroom')
                                ->getStateUsing(fn (Student $student) => $student->currentEnrollment()?->classroom?->name ?? '—'),

                            TextEntry::make('current_arm')
                                ->label('Class Arm')
                                ->getStateUsing(fn (Student $student) => $student->currentEnrollment()?->classArm?->name ?? '—'),

                            TextEntry::make('current_department')
                                ->label('Department')
                                ->getStateUsing(fn (Student $student) => $student->currentEnrollment()?->department?->name ?? '—'),
                        ]),
                    ]),

                Section::make('Biometric Identity & Verification')
                    ->icon('heroicon-o-finger-print')
                    ->description('Stored biometric minutiae used for campus access, examination verification, and hands-free attendance.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('biometric_status')
                                ->label('Enrollment Status')
                                ->badge()
                                ->getStateUsing(fn (Student $student) => $student->isBiometricEnrolled() ? 'Enrolled' : 'Not Enrolled')
                                ->color(fn (string $state) => $state === 'Enrolled' ? 'success' : 'gray')
                                ->icon(fn (string $state) => $state === 'Enrolled' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),

                            TextEntry::make('primary_fingerprint')
                                ->label('Primary Fingerprint')
                                ->badge()
                                ->getStateUsing(fn (Student $student) => ! empty($student->primary_biometric_template) ? 'Captured (ANSI 378)' : 'Missing')
                                ->color(fn (string $state) => str_starts_with((string) $state, 'Captured') ? 'success' : 'gray'),

                            TextEntry::make('backup_fingerprint')
                                ->label('Backup Fingerprint')
                                ->badge()
                                ->getStateUsing(fn (Student $student) => ! empty($student->backup_biometric_template) ? 'Captured (ANSI 378)' : 'None')
                                ->color(fn (string $state) => str_starts_with((string) $state, 'Captured') ? 'info' : 'gray'),

                            TextEntry::make('biometric_enrolled_at')
                                ->label('Enrolled On')
                                ->dateTime('M d, Y h:i A')
                                ->placeholder('Not yet enrolled'),
                        ]),
                    ]),
            ]);
    }
}
