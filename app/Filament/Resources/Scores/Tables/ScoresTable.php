<?php

namespace App\Filament\Resources\Scores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class ScoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($record) => $record->student->first_name . ' ' . $record->student->last_name)
                    ->searchable(['students.first_name', 'students.last_name'])
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Classroom')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('scoreHeader.name')
                    ->label('Score Type')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('value')
                    ->label('Score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('session')
                    ->label('Session')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('term')
                    ->label('Term')
                    ->formatStateUsing(fn ($state) => match($state) {
                        1 => 'First Term',
                        2 => 'Second Term',
                        3 => 'Third Term',
                        default => $state
                    })
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
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
