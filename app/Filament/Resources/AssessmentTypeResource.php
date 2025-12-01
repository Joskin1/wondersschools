<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessmentTypeResource\Pages;
use App\Models\AssessmentType;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class AssessmentTypeResource extends Resource
{
    protected static ?string $model = AssessmentType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('max_score')
                    ->required()
                    ->numeric()
                    ->maxValue(100)
                    ->rules([
                        function ($get, ?AssessmentType $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                if (!$get('is_active')) {
                                    return;
                                }
                                
                                $totalMaxScore = AssessmentType::where('is_active', true)
                                    ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                    ->sum('max_score');
                                
                                if ($totalMaxScore + $value > 100) {
                                    $fail("The total max score of active assessment types cannot exceed 100. Current total: {$totalMaxScore}.");
                                }
                            };
                        },
                    ]),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->rules([
                        function ($get, ?AssessmentType $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                if (!$value) {
                                    return;
                                }
                                
                                $maxScore = $get('max_score');
                                if (!$maxScore) {
                                    return; 
                                }

                                $totalMaxScore = AssessmentType::where('is_active', true)
                                    ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                    ->sum('max_score');
                                
                                if ($totalMaxScore + $maxScore > 100) {
                                    $fail("Activating this assessment type would exceed the total max score of 100. Current total: {$totalMaxScore}.");
                                }
                            };
                        },
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListAssessmentTypes::route('/'),
            'create' => Pages\CreateAssessmentType::route('/create'),
            'edit' => Pages\EditAssessmentType::route('/{record}/edit'),
        ];
    }
}
