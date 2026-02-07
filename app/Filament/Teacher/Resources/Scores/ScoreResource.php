<?php

namespace App\Filament\Teacher\Resources\Scores;

use App\Filament\Teacher\Resources\Scores\Pages\CreateScore;
use App\Filament\Teacher\Resources\Scores\Pages\EditScore;
use App\Filament\Teacher\Resources\Scores\Pages\ListScores;
use App\Models\Score;
use App\Services\ScoreService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Forms\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ScoreResource extends Resource
{
    protected static ?string $model = Score::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;
    
    protected static string|\UnitEnum|null $navigationGroup = 'Score Management';
    
    protected static ?string $navigationLabel = 'Scores';
    
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Section::make('Score Information')
                    ->schema([
                        Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Components\Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Components\Select::make('classroom_id')
                            ->relationship('classroom', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Components\Select::make('score_header_id')
                            ->relationship('scoreHeader', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Components\TextInput::make('session')
                            ->required()
                            ->default(fn() => now()->year . '/' . (now()->year + 1)),
                        
                        Components\Select::make('term')
                            ->options([
                                1 => 'First Term',
                                2 => 'Second Term',
                                3 => 'Third Term',
                            ])
                            ->required(),
                        
                        Components\TextInput::make('value')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('classroom.name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('scoreHeader.name')
                    ->label('Score Type')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('session')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('term')
                    ->formatStateUsing(fn($state) => match($state) {
                        1 => 'First Term',
                        2 => 'Second Term',
                        3 => 'Third Term',
                        default => $state,
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('value')
                    ->numeric(2)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session')
                    ->options(function () {
                        return Score::query()
                            ->distinct()
                            ->pluck('session', 'session')
                            ->toArray();
                    }),
                
                Tables\Filters\SelectFilter::make('term')
                    ->options([
                        1 => 'First Term',
                        2 => 'Second Term',
                        3 => 'Third Term',
                    ]),
                
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        
        // If user is a teacher, scope to their assignments
        if ($user && $user->isTeacher()) {
            $scoreService = app(ScoreService::class);
            return $scoreService->getScoresQueryForTeacher($user);
        }
        
        return $query;
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
            'index' => ListScores::route('/'),
            'create' => CreateScore::route('/create'),
            'edit' => EditScore::route('/{record}/edit'),
        ];
    }
}
