<?php

namespace App\Filament\Teacher\Resources\Scores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subject_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('classroom_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('score_header_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('session')
                    ->searchable(),
                TextColumn::make('term')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('academic_session_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('term_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ca_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('exam_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
}
