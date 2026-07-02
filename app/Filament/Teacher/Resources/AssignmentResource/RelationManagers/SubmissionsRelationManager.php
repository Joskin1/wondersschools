<?php

namespace App\Filament\Teacher\Resources\AssignmentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\RepeatableEntry;
use Filament\Schemas\Components\Grid;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $recordTitleAttribute = 'student.full_name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn ($record) => "{$record->score} / {$record->total_points}")
                    ->badge()
                    ->color(fn ($record) => $record->percentageScore() >= 50 ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('percentageScore')
                    ->label('Percentage')
                    ->formatStateUsing(fn ($record) => $record->percentageScore() . '%')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('Submission Details')
                    ->infolist(function (Schema $schema) {
                        return $schema
                            ->schema([
                                Section::make('Student Result')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextEntry::make('student.full_name')->label('Student'),
                                            TextEntry::make('score')
                                                ->label('Score')
                                                ->formatStateUsing(fn ($record) => "{$record->score} / {$record->total_points}")
                                                ->badge(),
                                            TextEntry::make('percentageScore')
                                                ->label('Percentage')
                                                ->formatStateUsing(fn ($record) => $record->percentageScore() . '%'),
                                        ]),
                                    ]),
                            ]);
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }
}
