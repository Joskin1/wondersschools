<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicSessionResource\Pages;
use App\Models\Session;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AcademicSessionResource extends Resource
{
    protected static ?string $model = Session::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., 2024-2025')
                    ->helperText('Format: YYYY-YYYY'),
                
                TextInput::make('start_year')
                    ->required()
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->placeholder('e.g., 2024'),
                
                TextInput::make('end_year')
                    ->required()
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->placeholder('e.g., 2025'),
                
                Toggle::make('is_active')
                    ->label('Active Session')
                    ->helperText('Only one session can be active at a time')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('start_year')
                    ->sortable()
                    ->label('Start Year'),
                
                Tables\Columns\TextColumn::make('end_year')
                    ->sortable()
                    ->label('End Year'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('terms_count')
                    ->counts('terms')
                    ->label('Terms')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All sessions')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // No bulk delete - preserve historical data
            ])
            ->defaultSort('start_year', 'desc');
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
            'index' => Pages\ListSessions::route('/'),
            'create' => Pages\CreateSession::route('/create'),
            'view' => Pages\ViewSession::route('/{record}'),
            'edit' => Pages\EditSession::route('/{record}/edit'),
        ];
    }
}
