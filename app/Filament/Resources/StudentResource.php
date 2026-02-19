<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Session;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;


class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Students';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., John Doe'),

                Select::make('classroom_id')
                    ->label('Classroom')
                    ->required()
                    ->relationship('enrollments.classroom', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Select the classroom for this student.'),

                Select::make('session_id')
                    ->label('Academic Session')
                    ->required()
                    ->options(Session::pluck('name', 'id'))
                    ->default(fn () => Session::where('is_active', true)->first()?->id)
                    ->searchable()
                    ->helperText('Select the academic session for enrollment.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                    ])
                    ->sortable(),

                TextColumn::make('enrollments.classroom.name')
                    ->label('Current Classroom')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

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
                    ->color(fn ($record) => $record->hasExpiredRegistration() ? 'danger' : 'warning'),

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
            ])
            ->actions([
                Action::make('generate_registration_link')
                    ->label('Generate Link')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->visible(fn (Student $record) => $record->isPending() && !$record->registration_slug)
                    ->modalHeading('Registration Link Information')
                    ->modalContent(function (Student $record) {
                        // Generate the link when the modal opens
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
                            'note' => 'The full link with token is shown above. This link will expire in 3 days and can only be used once.',
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('lg'),
                    
                ViewAction::make(),
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
            'view' => Pages\ViewStudent::route('/{record}'),
        ];
    }

    public static function canDelete($record): bool
    {
        // Prevent deletion to preserve historical records
        return false;
    }
}
