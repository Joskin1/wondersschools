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

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Portal Access')
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
                        'sudo' => 'Sudo',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Action::make('toggle_portal_access')
                    ->label(fn (User $record) => $record->isActive() ? 'Deactivate Portal' : 'Activate Portal')
                    ->icon(fn (User $record) => $record->isActive() ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record) => $record->isActive() ? 'warning' : 'success')
                    ->visible(fn (User $record) => $record->role === 'teacher')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => $record->isActive() ? 'Deactivate Teacher Portal Access' : 'Activate Teacher Portal Access')
                    ->modalDescription(fn (User $record) => $record->isActive()
                        ? "Deactivate portal access for {$record->name}? They will no longer be able to log in."
                        : "Activate portal access for {$record->name}? They will receive a welcome email with a link to log into their teacher portal."
                    )
                    ->action(function (User $record) {
                        $newStatus = !$record->isActive();
                        $record->update(['is_active' => $newStatus]);

                        if ($newStatus) {
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
                        } else {
                            Notification::make()
                                ->title('Portal Access Deactivated')
                                ->body("Portal access disabled for {$record->name}.")
                                ->info()
                                ->send();
                        }
                    }),

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
