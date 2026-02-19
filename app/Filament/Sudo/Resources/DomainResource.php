<?php

namespace App\Filament\Sudo\Resources;

use App\Models\Central\Domain;
use App\Models\Central\School;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string | \UnitEnum | null $navigationGroup = 'Tenant Management';

    protected static ?string $navigationLabel = 'Domains';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_id')
                    ->label('School')
                    ->options(School::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->native(false),

                TextInput::make('domain')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Enter the full domain (e.g., schoola.wonders.test or school.example.com)')
                    ->rules(['regex:/^[a-zA-Z0-9][a-zA-Z0-9\.\-]*[a-zA-Z0-9]$/'])
                    ->validationMessages([
                        'regex' => 'Domain must contain only letters, numbers, dots, and hyphens.',
                    ]),

                Toggle::make('is_primary')
                    ->label('Primary Domain')
                    ->default(false)
                    ->helperText('Only one domain should be marked as primary per school.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('school.status')
                    ->label('School Status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                    ]),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('School')
                    ->options(School::pluck('name', 'id')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Sudo\Resources\DomainResource\Pages\ListDomains::route('/'),
            'create' => \App\Filament\Sudo\Resources\DomainResource\Pages\CreateDomain::route('/create'),
            'edit' => \App\Filament\Sudo\Resources\DomainResource\Pages\EditDomain::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
