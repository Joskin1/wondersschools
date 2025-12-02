<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScoreResource\Pages;
use App\Models\Score;
use App\Models\EvaluationSetting;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Auth;

class ScoreResource extends Resource
{
    protected static ?string $model = Score::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('academic_session_id')
                    ->relationship('academicSession', 'name')
                    ->required()
                    ->default(fn () => \App\Models\AcademicSession::where('is_current', true)->first()?->id)
                    ->live(),
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
                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn () => Auth::user()?->staff?->id),
                Forms\Components\TextInput::make('ca_score')
                    ->label(function ($get) {
                        $sessionId = $get('academic_session_id');
                        if ($sessionId) {
                            $caSetting = EvaluationSetting::where('academic_session_id', $sessionId)
                                ->where('name', 'CA')
                                ->first();
                            return 'CA Score (Max: ' . ($caSetting?->max_score ?? 40) . ')';
                        }
                        return 'CA Score';
                    })
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(function ($get) {
                        $sessionId = $get('academic_session_id');
                        if ($sessionId) {
                            $caSetting = EvaluationSetting::where('academic_session_id', $sessionId)
                                ->where('name', 'CA')
                                ->first();
                            return $caSetting?->max_score ?? 40;
                        }
                        return 40;
                    })
                    ->default(0),
                Forms\Components\TextInput::make('exam_score')
                    ->label(function ($get) {
                        $sessionId = $get('academic_session_id');
                        if ($sessionId) {
                            $examSetting = EvaluationSetting::where('academic_session_id', $sessionId)
                                ->where('name', 'Exam')
                                ->first();
                            return 'Exam Score (Max: ' . ($examSetting?->max_score ?? 60) . ')';
                        }
                        return 'Exam Score';
                    })
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(function ($get) {
                        $sessionId = $get('academic_session_id');
                        if ($sessionId) {
                            $examSetting = EvaluationSetting::where('academic_session_id', $sessionId)
                                ->where('name', 'Exam')
                                ->first();
                            return $examSetting?->max_score ?? 60;
                        }
                        return 60;
                    })
                    ->default(0),
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
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ca_score')
                    ->label('CA')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_score')
                    ->label('Exam')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->label('Total')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),
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
