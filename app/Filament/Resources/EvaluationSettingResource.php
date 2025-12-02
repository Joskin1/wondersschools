<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationSettingResource\Pages;
use App\Filament\Resources\EvaluationSettingResource\RelationManagers;
use App\Models\EvaluationSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class EvaluationSettingResource extends Resource
{
    protected static ?string $model = EvaluationSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('academic_session_id')
                    ->relationship('academicSession', 'name')
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('max_score')
                    ->required()
                    ->numeric()
                    ->maxValue(100)
                    ->rule(function ($get, ?EvaluationSetting $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $sessionId = $get('academic_session_id');
                            if (!$sessionId) return;

                            $query = EvaluationSetting::where('academic_session_id', $sessionId);
                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }
                            
                            $currentTotal = $query->sum('max_score');
                            
                            if (($currentTotal + $value) > 100) {
                                $fail("The total max score for this session cannot exceed 100. Current total: {$currentTotal}.");
                            }
                        };
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicSession.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_score')
                    ->numeric()
                    ->sortable(),
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
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListEvaluationSettings::route('/'),
            'create' => Pages\CreateEvaluationSetting::route('/create'),
            'edit' => Pages\EditEvaluationSetting::route('/{record}/edit'),
        ];
    }
}
