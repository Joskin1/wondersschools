<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\ClassArm;
use App\Models\Session;
use App\Models\Student;
use App\Services\FrontendLibrary;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use STS\FilamentImpersonate\Actions\Impersonate;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Students';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminPage('students') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('surname')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Adeyemi'),

                TextInput::make('first_name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Oluwadamilare'),

                TextInput::make('other_name')
                    ->maxLength(255)
                    ->placeholder('e.g., John (optional)'),

                TextInput::make('initial_password')
                    ->label('Initial Password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->dehydrated(fn ($state): bool => filled($state)),

                Select::make('classroom_id')
                    ->label('Classroom')
                    ->required()
                    ->options(fn () => \App\Models\Classroom::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->helperText('Select the classroom for this student.'),

                Select::make('class_arm_id')
                    ->label('Class Arm / Stream')
                    ->options(function (callable $get) {
                        $classroomId = $get('classroom_id');
                        if (! $classroomId) {
                            return [];
                        }
                        return ClassArm::where('classroom_id', $classroomId)
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Select arm (optional)')
                    ->visible(fn () => FrontendLibrary::getBooleanSetting('enable_class_arms'))
                    ->helperText('Select the specific arm or section for this student (optional).'),

                Select::make('department_id')
                    ->label('Department')
                    ->options(fn () => \App\Models\Department::active()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('Select department')
                    ->visible(function (callable $get) {
                        $classroomId = $get('classroom_id');
                        if (! $classroomId) {
                            return false;
                        }
                        return (bool) \App\Models\Classroom::where('id', $classroomId)->value('has_departments');
                    })
                    ->required(function (callable $get) {
                        $classroomId = $get('classroom_id');
                        if (! $classroomId) {
                            return false;
                        }
                        return (bool) \App\Models\Classroom::where('id', $classroomId)->value('has_departments');
                    })
                    ->helperText('Select the academic department/stream for this senior school student.'),

                Select::make('session_id')
                    ->label('Academic Session')
                    ->required()
                    ->options(fn () => Session::pluck('name', 'id'))
                    ->default(fn () => Session::where('is_active', true)->first()?->id)
                    ->searchable()
                    ->helperText('Select the academic session for enrollment.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_picture')
                    ->label('')
                    ->circular()
                    ->disk(config('filesystems.upload_disk', 'public'))
                    ->defaultImageUrl(fn (Student $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=6366f1&color=fff&size=40')
                    ->size(40)
                    ->grow(false),

                TextColumn::make('user.surname')
                    ->label('Student Name')
                    ->formatStateUsing(fn (Student $record): string => $record->full_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->where('surname', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('other_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('admission_number')
                    ->label('Adm No')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('registration_status')
                    ->label('Registration')
                    ->badge()
                    ->getStateUsing(function (Student $record): string {
                        if ($record->is_portal_active) {
                            return 'Active';
                        }
                        if ($record->isRegistrationCompleted()) {
                            return 'Awaiting Activation';
                        }

                        return 'Pending';
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'Active' => 'success',
                        'Awaiting Activation' => 'warning',
                        'Pending' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'Active' => 'heroicon-o-check-circle',
                        'Awaiting Activation' => 'heroicon-o-clock',
                        'Pending' => 'heroicon-o-ellipsis-horizontal-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                TextColumn::make('biometric_status')
                    ->label('Biometrics')
                    ->badge()
                    ->getStateUsing(fn (Student $record): string => $record->primary_biometric_template ? 'Enrolled' : 'Not Enrolled')
                    ->color(fn (string $state): string => $state === 'Enrolled' ? 'success' : 'gray')
                    ->icon(fn (string $state): string => $state === 'Enrolled' ? 'heroicon-o-finger-print' : 'heroicon-o-x-circle')
                    ->description(fn (Student $record): ?string => $record->biometric_enrolled_at?->format('M d, Y'))
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('biometric_enrolled_at', $direction)),

                ToggleColumn::make('is_portal_active')
                    ->label('Portal')
                    ->disabled(fn (Student $record): bool => ! $record->isRegistrationCompleted())
                    ->afterStateUpdated(function (Student $record, bool $state) {
                        if ($state) {
                            $record->activatePortal(auth()->id());
                        } else {
                            $record->deactivatePortal();
                        }
                    }),

                TextColumn::make('enrollments.classroom.name')
                    ->label('Classroom')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

                TextColumn::make('enrollments.classArm.name')
                    ->label('Arm')
                    ->badge()
                    ->color('primary')
                    ->placeholder('—')
                    ->visible(fn () => FrontendLibrary::getBooleanSetting('enable_class_arms')),

                TextColumn::make('enrollments.department.name')
                    ->label('Department')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                TextColumn::make('enrollments.session.name')
                    ->label('Session')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

                TextColumn::make('registration_expires_at')
                    ->label('Link Expires')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn ($record) => $record->hasExpiredRegistration() ? 'danger' : 'warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                    ]),

                SelectFilter::make('session_id')
                    ->label('Session')
                    ->relationship('enrollments.session', 'name'),

                SelectFilter::make('classroom_id')
                    ->label('Classroom')
                    ->relationship('enrollments.classroom', 'name'),

                SelectFilter::make('class_arm_id')
                    ->label('Arm')
                    ->options(fn () => ClassArm::pluck('name', 'id'))
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('enrollments', fn ($q) => $q->where('class_arm_id', $data['value']));
                        }
                    })
                    ->visible(fn () => FrontendLibrary::getBooleanSetting('enable_class_arms')),

                SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(fn () => \App\Models\Department::pluck('name', 'id'))
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('enrollments', fn ($q) => $q->where('department_id', $data['value']));
                        }
                    }),

                TernaryFilter::make('biometrics')
                    ->label('Biometric Enrollment')
                    ->placeholder('All Students')
                    ->trueLabel('Enrolled Only')
                    ->falseLabel('Not Enrolled Only')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('primary_biometric_template'),
                        false: fn ($query) => $query->whereNull('primary_biometric_template'),
                    ),

                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('biometrics')
                    ->label('Biometrics')
                    ->icon('heroicon-o-finger-print')
                    ->color(fn (Student $record) => $record->primary_biometric_template ? 'success' : 'gray')
                    ->modalHeading(fn (Student $record) => "Biometric Identity: {$record->full_name}")
                    ->modalContent(fn (Student $record) => view('filament.modals.student-biometric-info', [
                        'student' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

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

                ViewAction::make(),
                Action::make('reset_biometrics')
                    ->label('Reset Biometrics')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->visible(fn (Student $record) => ! empty($record->primary_biometric_template))
                    ->requiresConfirmation()
                    ->modalHeading('Reset Biometric Data')
                    ->modalDescription(fn (Student $record) => "Are you sure you want to clear biometric templates for {$record->full_name}? The student will need to be re-scanned on a biometric enrollment station.")
                    ->action(function (Student $record) {
                        $record->clearBiometrics();
                        \Filament\Notifications\Notification::make()
                            ->title('Biometric Data Cleared')
                            ->body("Biometric templates for {$record->full_name} have been cleared.")
                            ->warning()
                            ->send();
                    }),
                DeleteAction::make(),
                RestoreAction::make(),
                Impersonate::make()
                    ->impersonateRecord(fn ($record) => $record->user)
                    ->redirectTo('/student')
                    ->visible(fn ($record) => $record->user !== null && $record->is_portal_active),
            ])
            ->bulkActions([
                // No bulk actions for data integrity
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'import' => Pages\ImportStudents::route('/import'),
            'view' => Pages\ViewStudent::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['enrollments.classroom', 'enrollments.session', 'user']);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSudo() || auth()->user()?->isAdmin();
    }
}
