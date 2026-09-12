<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassGroupResource\Pages;
use App\Models\ClassGroup;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;

class ClassGroupResource extends Resource
{
    protected static ?string $model = ClassGroup::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Class Groups';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Primary, Junior Secondary (JSS), Senior Secondary (SSS)')
                    ->helperText('Name of the class category/group'),

                TextInput::make('order')
                    ->label('Display Order')
                    ->numeric()
                    ->integer()
                    ->default(0)
                    ->helperText('Defines sorting order among class groups.'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive class groups are hidden from filters.'),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->placeholder('Optional description for this group...')
                    ->columnSpanFull(),

                Select::make('classrooms')
                    ->label('Classes in this Group')
                    ->relationship('classrooms', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Assign classrooms that belong to this group/category.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('classrooms.name')
                    ->label('Classes')
                    ->badge()
                    ->color('info')
                    ->separator(', '),

                TextColumn::make('classrooms_count')
                    ->counts('classrooms')
                    ->label('Total Classes')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('order')
                    ->label('Order')
                    ->sortable()
                    ->badge(),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('order', 'asc');
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
            'index' => Pages\ListClassGroups::route('/'),
            'create' => Pages\CreateClassGroup::route('/create'),
            'edit' => Pages\EditClassGroup::route('/{record}/edit'),
        ];
    }
}
