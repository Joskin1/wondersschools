<?php

namespace App\Filament\Sudo\Resources;

use App\Filament\Sudo\Resources\TenantAdminResource\Pages\ManageTenantAdmins;
use App\Models\Tenant;
use App\Models\TenantAdminAssignment;
use Filament\Actions;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TenantAdminResource extends Resource
{
    protected static ?string $model = TenantAdminAssignment::class;
    protected static ?string $navigationLabel = 'School Admins';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'School Admin';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('tenant_id')
                    ->label('School')
                    ->options(fn () => Tenant::on('landlord')->get()
                        ->mapWithKeys(fn ($tenant) => [$tenant->id => $tenant->name ?? $tenant->id])
                    )
                    ->required()
                    ->searchable(),

                Select::make('role')
                    ->options([
                        'admin'   => 'Admin',
                        'teacher' => 'Teacher',
                    ])
                    ->default('admin')
                    ->required(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('School')
                    ->formatStateUsing(fn (string $state): string =>
                        Tenant::on('landlord')->find($state)?->name ?? "({$state})"
                    )
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin'   => 'warning',
                        'teacher' => 'info',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('credentials_sent_at')
                    ->label('Credentials Sent')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not sent'),
            ])
            ->actions([
                Actions\Action::make('resend')
                    ->label('Resend Credentials')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (TenantAdminAssignment $record): void {
                        ManageTenantAdmins::provisionAdmin([
                            'tenant_id' => $record->tenant_id,
                            'email'     => $record->email,
                            'name'      => $record->name,
                            'role'      => $record->role,
                        ], $record);
                    }),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTenantAdmins::route('/'),
        ];
    }
}
