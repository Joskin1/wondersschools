<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DesktopDeviceResource\Pages;
use App\Models\DesktopDevice;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DesktopDeviceResource extends Resource
{
    protected static ?string $model = DesktopDevice::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-finger-print';

    protected static string | \UnitEnum | null $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Biometric Devices';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminPage('desktop_devices') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device_name')
                    ->label('Device Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('device_token_prefix')
                    ->label('Token')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('pairedBy.full_name')
                    ->label('Paired By')
                    ->placeholder('System / Admin')
                    ->searchable(),

                TextColumn::make('last_ip')
                    ->label('Last IP')
                    ->placeholder('—'),

                TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Paired On')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active Only')
                    ->falseLabel('Revoked Only'),
            ])
            ->actions([
                Action::make('revoke')
                    ->label('Revoke Access')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke Device Access')
                    ->modalDescription('Revoking this device will immediately prevent it from syncing attendance or accessing school rosters.')
                    ->visible(fn (DesktopDevice $record) => $record->is_active)
                    ->action(function (DesktopDevice $record) {
                        $record->update(['is_active' => false]);
                        Notification::make()
                            ->title('Device Access Revoked')
                            ->body("Device '{$record->device_name}' has been deactivated.")
                            ->warning()
                            ->send();
                    }),

                Action::make('restore_access')
                    ->label('Restore Access')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Restore Device Access')
                    ->modalDescription('Restoring this device will allow it to resume syncing attendance.')
                    ->visible(fn (DesktopDevice $record) => ! $record->is_active)
                    ->action(function (DesktopDevice $record) {
                        $record->update(['is_active' => true]);
                        Notification::make()
                            ->title('Device Access Restored')
                            ->body("Device '{$record->device_name}' is now active.")
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDesktopDevices::route('/'),
        ];
    }
}
