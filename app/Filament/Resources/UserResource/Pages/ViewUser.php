<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $currentUser = auth()->user();
        $isSudo = $currentUser?->isSudo();

        return [
            // Role change action — available to both admin and sudo for non-student users
            Action::make('change_role')
                ->label('Change Role')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => !$this->record->trashed() && !$this->record->isSudo() && $this->record->role !== 'student')
                ->form([
                    \Filament\Forms\Components\Select::make('role')
                        ->label('New Role')
                        ->options(
                            $isSudo
                                ? ['teacher' => 'Teacher', 'admin' => 'Admin', 'sudo' => 'Sudo']
                                : ['teacher' => 'Teacher', 'admin' => 'Admin']
                        )
                        ->default(fn () => $this->record->role)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Change User Role')
                ->modalDescription(fn () => "Change role for {$this->record->name} ({$this->record->email})")
                ->action(function (array $data) {
                    $this->record->update(['role' => $data['role']]);
                    \Filament\Notifications\Notification::make()
                        ->title('Role Updated')
                        ->body("{$this->record->name} is now a " . ucfirst($data['role']) . ".")
                        ->success()
                        ->send();
                }),

            // Edit — sudo only
            \Filament\Actions\EditAction::make()
                ->visible(fn () => $isSudo && !$this->record->trashed()),

            DeleteAction::make()
                ->visible(fn () => $isSudo && !$this->record->trashed()),

            RestoreAction::make()
                ->visible(fn () => $isSudo && $this->record->trashed()),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->record;
        $uploadDisk = config('filesystems.upload_disk', 'public');

        return $schema
            ->schema([
                // ── Account Information ──
                Section::make('Account Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(3)->schema([
                            ImageEntry::make('avatar_url')
                                ->label('')
                                ->circular()
                                ->size(80)
                                ->getStateUsing(function () use ($record, $uploadDisk): string {
                                    $avatarPath = null;
                                    if ($record->isStudent() && $record->student) {
                                        $avatarPath = $record->student->profile_picture;
                                    } elseif ($record->isTeacher() && $record->teacher) {
                                        $avatarPath = $record->teacher->profile_picture;
                                    }
                                    if ($avatarPath) {
                                        return Storage::disk($uploadDisk)->url($avatarPath);
                                    }
                                    return 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=6366f1&color=fff&size=80';
                                }),
                            TextEntry::make('name')
                                ->label('Full Name')
                                ->weight('bold')
                                ->size('lg'),
                            TextEntry::make('role')
                                ->label('Role')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'sudo' => 'danger',
                                    'admin' => 'warning',
                                    'teacher' => 'success',
                                    'student' => 'info',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('email')
                                ->label('Email Address')
                                ->icon('heroicon-o-envelope')
                                ->copyable(),
                            TextEntry::make('is_active')
                                ->label('Portal Access')
                                ->badge()
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                                ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),
                            TextEntry::make('email_verified_at')
                                ->label('Email Verified')
                                ->dateTime('M d, Y H:i')
                                ->placeholder('Not verified'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('registration_completed_at')
                                ->label('Registration Completed')
                                ->dateTime('M d, Y H:i')
                                ->placeholder('Not completed'),
                            TextEntry::make('created_at')
                                ->label('Account Created')
                                ->dateTime('M d, Y H:i'),
                            TextEntry::make('updated_at')
                                ->label('Last Updated')
                                ->dateTime('M d, Y H:i'),
                        ]),
                    ]),

                // ── Teacher Profile (only for teachers) ──
                Section::make('Teacher Profile')
                    ->icon('heroicon-o-briefcase')
                    ->visible(fn () => $record->isTeacher() && $record->teacher)
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('teacher.phone')
                                ->label('Phone Number')
                                ->icon('heroicon-o-phone')
                                ->placeholder('Not provided')
                                ->copyable(),
                            TextEntry::make('teacher.gender')
                                ->label('Gender')
                                ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—')
                                ->placeholder('Not provided'),
                            TextEntry::make('teacher.dob')
                                ->label('Date of Birth')
                                ->date('M d, Y')
                                ->placeholder('Not provided'),
                        ]),
                        TextEntry::make('teacher.address')
                            ->label('Address')
                            ->icon('heroicon-o-map-pin')
                            ->placeholder('Not provided')
                            ->columnSpanFull(),
                    ]),

                // ── Student Profile (only for students) ──
                Section::make('Student Profile')
                    ->icon('heroicon-o-academic-cap')
                    ->visible(fn () => $record->isStudent() && $record->student)
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('student.admission_number')
                                ->label('Admission Number')
                                ->badge()
                                ->color('primary')
                                ->copyable(),
                            TextEntry::make('student.gender')
                                ->label('Gender')
                                ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—')
                                ->placeholder('Not provided'),
                            TextEntry::make('student.date_of_birth')
                                ->label('Date of Birth')
                                ->date('M d, Y')
                                ->placeholder('Not provided'),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('student.address')
                                ->label('Address')
                                ->icon('heroicon-o-map-pin')
                                ->placeholder('Not provided'),
                            TextEntry::make('student.previous_school')
                                ->label('Previous School')
                                ->icon('heroicon-o-building-library')
                                ->placeholder('Not provided'),
                        ]),
                    ]),

                // ── Parent / Guardian Information (only for students) ──
                Section::make('Parent / Guardian Information')
                    ->icon('heroicon-o-users')
                    ->visible(fn () => $record->isStudent() && $record->student)
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('student.parent_name')
                                ->label('Parent Name')
                                ->placeholder('Not provided'),
                            TextEntry::make('student.parent_phone')
                                ->label('Parent Phone')
                                ->icon('heroicon-o-phone')
                                ->placeholder('Not provided')
                                ->copyable(),
                            TextEntry::make('student.parent_email')
                                ->label('Parent Email')
                                ->icon('heroicon-o-envelope')
                                ->placeholder('Not provided')
                                ->copyable(),
                        ]),
                    ]),

                // ── Student Enrollment (only for students) ──
                Section::make('Current Enrollment')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->visible(fn () => $record->isStudent() && $record->student)
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('classroom_name')
                                ->label('Classroom')
                                ->getStateUsing(fn () => $record->student?->currentEnrollment()?->classroom?->name ?? '—'),
                            TextEntry::make('session_name')
                                ->label('Academic Session')
                                ->getStateUsing(fn () => $record->student?->currentEnrollment()?->session?->name ?? '—'),
                            TextEntry::make('student_portal_status')
                                ->label('Student Portal')
                                ->badge()
                                ->getStateUsing(fn (): string => $record->student?->is_portal_active ? 'Active' : 'Inactive')
                                ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'danger'),
                        ]),
                    ]),

                // ── Teacher Assignments (only for teachers) ──
                Section::make('Subject Assignments')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->visible(fn () => $record->isTeacher())
                    ->schema([
                        TextEntry::make('teacher_assignments')
                            ->label('')
                            ->getStateUsing(function () use ($record): string {
                                $assignments = \App\Models\TeacherSubjectAssignment::where('teacher_id', $record->id)
                                    ->with(['subject', 'classroom', 'session', 'term'])
                                    ->where('status', 'approved')
                                    ->latest()
                                    ->get();

                                if ($assignments->isEmpty()) {
                                    return 'No subject assignments found.';
                                }

                                $rows = $assignments->map(function ($a) {
                                    $subject = $a->subject?->name ?? '—';
                                    $class = $a->classroom?->name ?? '—';
                                    $session = $a->session?->name ?? '—';
                                    $term = $a->term?->name ?? '—';
                                    return "<tr><td style='padding:6px 12px'>{$subject}</td><td style='padding:6px 12px'>{$class}</td><td style='padding:6px 12px'>{$session}</td><td style='padding:6px 12px'>{$term}</td></tr>";
                                })->implode('');

                                return "<table style='width:100%;border-collapse:collapse'>"
                                    . "<thead><tr style='border-bottom:2px solid #e5e7eb'>"
                                    . "<th style='padding:6px 12px;text-align:left'>Subject</th>"
                                    . "<th style='padding:6px 12px;text-align:left'>Class</th>"
                                    . "<th style='padding:6px 12px;text-align:left'>Session</th>"
                                    . "<th style='padding:6px 12px;text-align:left'>Term</th>"
                                    . "</tr></thead>"
                                    . "<tbody>{$rows}</tbody></table>";
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
