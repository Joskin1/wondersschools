<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScoreResource\Pages;
use App\Models\Score;
use App\Models\AssessmentType;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ScoreResource extends Resource
{
    protected static ?string $model = Score::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('academic_session_id')
                    ->relationship('academicSession', 'name')
                    ->required()
                    ->default(fn () => \App\Models\AcademicSession::where('is_current', true)->first()?->id),
                Forms\Components\Select::make('term_id')
                    ->relationship('term', 'name')
                    ->required()
                    ->default(fn () => \App\Models\Term::where('is_current', true)->first()?->id),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->required()
                    ->searchable(['first_name', 'last_name'])
                    ->preload(),
                Forms\Components\Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('assessment_type_id')
                    ->relationship('assessmentType', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),
                Forms\Components\TextInput::make('score')
                    ->required()
                    ->numeric()
                    ->maxValue(function ($get) {
                        $assessmentTypeId = $get('assessment_type_id');
                        if ($assessmentTypeId) {
                            $assessmentType = AssessmentType::find($assessmentTypeId);
                            return $assessmentType ? $assessmentType->max_score : 100;
                        }
                        return 100;
                    })
                    ->label(function ($get) {
                        $assessmentTypeId = $get('assessment_type_id');
                        if ($assessmentTypeId) {
                            $assessmentType = AssessmentType::find($assessmentTypeId);
                            return 'Score (Max: ' . ($assessmentType ? $assessmentType->max_score : '?') . ')';
                        }
                        return 'Score';
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicSession.name')
                    ->label('Session')
                    ->sortable(),
                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($record) => $record->student->full_name ?? '')
                    ->sortable()
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('subject.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('assessmentType.name')
                    ->label('Assessment Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('score')
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
            'index' => Pages\ListScores::route('/'),
            'create' => Pages\CreateScore::route('/create'),
            'edit' => Pages\EditScore::route('/{record}/edit'),
            'bulk-input' => Pages\BulkScoreInput::route('/bulk-input'),
        ];
    }
}
