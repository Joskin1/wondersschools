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
use Filament\Actions\DeleteAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    /**
     * Exclude sudo users from tenant admin panel for security.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', '!=', 'sudo');
    }

    public static function form(Schema $schema): Schema
    {
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
                    ->options([
                        'teacher' => 'Teacher',
                        'admin' => 'Admin',
                    ])
                    ->required()
                    ->default('teacher'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('registration_completed_at')
                    ->label('Registered')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'teacher' => 'Teacher',
                        'admin' => 'Admin',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Action::make('send_registration_link')
                    ->label('Send Registration Link')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->visible(fn (User $record) => 
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
                            // Generate token
                            $token = TeacherRegistrationToken::createForUser($record);
                            
                            // Send notification
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

                \STS\FilamentImpersonate\Actions\Impersonate::make()
                    ->redirectTo('/teacher'),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record) => !in_array($record->role, ['sudo'])),
            ])
            ->bulkActions([
                // No bulk actions for security
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
