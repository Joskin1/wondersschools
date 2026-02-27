<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScoreHeadResource\Pages;
use App\Models\ScoreHead;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Illuminate\Support\Facades\Auth;

class ScoreHeadResource extends Resource
{
    protected static ?string $model = ScoreHead::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    protected static string | \UnitEnum | null $navigationGroup = 'Results';

    protected static ?string $navigationLabel = 'Score Heads';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->placeholder('e.g. Classwork, Test 1, Exam'),

            TextInput::make('max_score')
                ->required()
                ->integer()
                ->minValue(1)
                ->maxValue(100)
                ->suffix('pts')
                ->helperText('Maximum points for this assessment component.'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive score heads cannot be added to new structures.'),
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

                TextColumn::make('max_score')
                    ->label('Max Score')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->suffix(' pts'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('structureItems_count')
                    ->counts('structureItems')
                    ->label('Used In')
                    ->badge()
                    ->color('success')
                    ->suffix(' structure(s)'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListScoreHeads::route('/'),
            'create' => Pages\CreateScoreHead::route('/create'),
            'edit'   => Pages\EditScoreHead::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageAcademics() ?? false;
    }
}
