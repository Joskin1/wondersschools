<?php

namespace App\Filament\Sudo\Resources;

use App\Models\Central\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string | \UnitEnum | null $navigationGroup = 'Tenant Management';

    protected static ?string $navigationLabel = 'Schools';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The name of the school. This will be used to generate the database name.'),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active')
                    ->required()
                    ->native(false)
                    ->visible(fn (string $context): bool => $context === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('database_name')
                    ->label('Database')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('domains.domain')
                    ->label('Domains')
                    ->badge()
                    ->separator(','),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                EditAction::make(),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Suspending this school will immediately block all access to its panel. Are you sure?')
                    ->visible(fn (School $record) => $record->isActive())
                    ->action(function (School $record) {
                        $record->update(['status' => 'suspended']);
                        Notification::make()
                            ->title('School Suspended')
                            ->body("'{$record->name}' has been suspended.")
                            ->warning()
                            ->send();
                    }),

                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (School $record) => $record->isSuspended())
                    ->action(function (School $record) {
                        $record->update(['status' => 'active']);
                        Notification::make()
                            ->title('School Activated')
                            ->body("'{$record->name}' is now active.")
                            ->success()
                            ->send();
                    }),

                Action::make('reset_password')
                    ->label('Reset Admin Password')
                    ->icon('heroicon-o-key')
                    ->color('gray')
                    ->form([
                        TextInput::make('new_password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->label('New Admin Password'),
                        TextInput::make('new_password_confirmation')
                            ->password()
                            ->required()
                            ->same('new_password')
                            ->label('Confirm Password'),
                    ])
                    ->action(function (School $record, array $data) {
                        tenancy()->initialize($record);
                        DB::table('users')->where('role', 'admin')->update(['password' => Hash::make($data['new_password'])]);
                        tenancy()->end();
                        
                        Notification::make()
                            ->title('Password Reset')
                            ->body("Admin password for '{$record->name}' has been reset.")
                            ->success()
                            ->send();
                    }),

                Action::make('delete_school')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This will permanently delete the school, its database, and all associated data. This action cannot be undone.')
                    ->action(function (School $record) {
                        // Deleting the model will trigger Stancl's TenantDeleted event
                        // This uses JobPipeline (DeleteDatabase, DeleteTenantDatabaseUser hooks)
                        $record->delete();
                        
                        Notification::make()
                            ->title('School Deleted')
                            ->body("'{$record->name}' and all its data have been permanently deleted.")
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Sudo\Resources\SchoolResource\Pages\ListSchools::route('/'),
            'create' => \App\Filament\Sudo\Resources\SchoolResource\Pages\CreateSchool::route('/create'),
            'edit' => \App\Filament\Sudo\Resources\SchoolResource\Pages\EditSchool::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
