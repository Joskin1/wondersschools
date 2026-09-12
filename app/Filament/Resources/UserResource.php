<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\TeacherRegistrationToken;
use App\Notifications\TeacherRegistrationInvitation;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\RestoreAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    /**
     * Build the base query for the resource.
     * Non-sudo users never see sudo records.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();
        if (!$user || !$user->isSudo()) {
            $query->where('role', '!=', 'sudo');
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        $currentUser = auth()->user();
        $isSudo = $currentUser && $currentUser->isSudo();

        $roleOptions = [
            'teacher' => 'Teacher',
            'admin' => 'Admin',
        ];

        // Only sudo users can assign sudo role
        if ($isSudo) {
            $roleOptions['sudo'] = 'Sudo';
        }

        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Select::make('role')
                    ->options($roleOptions)
                    ->required()
                    ->default('teacher')
                    // Prevent non-sudo from changing the role of a sudo user
                    ->disabled(fn (?User $record) => $record && $record->isSudo() && !$isSudo),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currentUser = auth()->user();
        $isSudo = $currentUser && $currentUser->isSudo();

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->getStateUsing(function (User $record): string {
                        $avatarPath = null;
                        if ($record->role === 'student' && $record->student) {
                            $avatarPath = $record->student->profile_picture;
                        } elseif ($record->role === 'teacher' && $record->teacher) {
                            $avatarPath = $record->teacher->profile_picture;
                        }
                        if ($avatarPath) {
                            return \Illuminate\Support\Facades\Storage::disk(config('filesystems.upload_disk', 'public'))->url($avatarPath);
                        }
                        return 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=6366f1&color=fff&size=40';
                    })
                    ->size(40)
                    ->grow(false),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sudo' => 'danger',
                        'admin' => 'warning',
                        'teacher' => 'success',
                        'student' => 'info',
                        default => 'gray',
                    }),

                // Portal Access toggle — editable for teachers, disabled for sudo/admin records
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Portal Access')
                    ->disabled(fn (User $record): bool => $record->trashed() || in_array($record->role, ['sudo', 'admin']))
                    ->afterStateUpdated(function (User $record, bool $state) {
                        if ($state && $record->role === 'teacher') {
                            try {
                                $record->notify(new \App\Notifications\TeacherPortalActivated());
                                Notification::make()
                                    ->title('Portal Access Activated')
                                    ->body("Welcome email sent to {$record->email}.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Log::error("Failed sending portal activation email to {$record->email}: " . $e->getMessage());
                                Notification::make()
                                    ->title('Portal Activated (Email Failed)')
                                    ->body("Activated {$record->name}, but email failed: " . $e->getMessage())
                                    ->warning()
                                    ->send();
                            }
                        } elseif (!$state && $record->role === 'teacher') {
                            Notification::make()
                                ->title('Portal Access Disabled')
                                ->body("Portal access disabled for {$record->name}.")
                                ->info()
                                ->send();
                        }
                    }),

                Tables\Columns\TextColumn::make('registration_completed_at')
                    ->label('Registered')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible($isSudo),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(
                        $isSudo
                            ? ['student' => 'Student', 'teacher' => 'Teacher', 'admin' => 'Admin', 'sudo' => 'Sudo']
                            : ['student' => 'Student', 'teacher' => 'Teacher', 'admin' => 'Admin']
                    ),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

            ])
            ->actions([
                // Send registration link — only for unregistered teachers
                Action::make('send_registration_link')
                    ->label('Send Registration Link')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->visible(fn (User $record) => 
                        !$record->trashed() &&
                        $record->role === 'teacher' && 
                        !$record->isActive() &&
                        !$record->hasCompletedRegistration()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Send Registration Link')
                    ->modalDescription(fn (User $record) => 
                        "Send a registration link to {$record->name} ({$record->email})? The link will expire in 3 days."
                    )
                    ->action(function (User $record) {
                        try {
                            $token = TeacherRegistrationToken::createForUser($record);
                            $record->notify(new TeacherRegistrationInvitation($token));
                            
                            Notification::make()
                                ->title('Registration link sent')
                                ->body("Email sent to {$record->email}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to send registration link')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Impersonate — nobody can impersonate sudo
                \STS\FilamentImpersonate\Actions\Impersonate::make()
                    ->visible(fn (User $record) => !$record->trashed() && !$record->isSudo())
                    ->redirectTo(fn (User $record) => match ($record->role) {
                        'admin' => '/admin',
                        'student' => '/student',
                        'sudo' => '/sudo',
                        default => '/teacher',
                    }),

                ViewAction::make(),

                // Change role — admins and sudo can change user role between teacher and admin
                Action::make('change_role')
                    ->label('Change Role')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (User $record) => !$record->trashed() && !$record->isSudo())
                    ->form([
                        Select::make('role')
                            ->label('New Role')
                            ->options(
                                $isSudo
                                    ? ['teacher' => 'Teacher', 'admin' => 'Admin', 'sudo' => 'Sudo']
                                    : ['teacher' => 'Teacher', 'admin' => 'Admin']
                            )
                            ->default(fn (User $record) => $record->role)
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Change User Role')
                    ->modalDescription(fn (User $record) => "Change role for {$record->name} ({$record->email})")
                    ->action(function (User $record, array $data) {
                        $record->update(['role' => $data['role']]);
                        Notification::make()
                            ->title('Role Updated')
                            ->body("{$record->name} is now a " . ucfirst($data['role']) . ".")
                            ->success()
                            ->send();
                    }),

                // Edit — only sudo can edit personal user data
                EditAction::make()
                    ->visible(fn (User $record) => $isSudo && !$record->trashed()),

                // Deleted users can only be restored from search results.
                RestoreAction::make()
                    ->visible(fn (User $record) => $isSudo && $record->trashed()),
            ])
            ->bulkActions([
                // No bulk actions for security
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isSudo() ?? false;
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
