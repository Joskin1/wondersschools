<?php

namespace App\Filament\Teacher\Resources\AssignmentResource\Pages;

use App\Filament\Teacher\Resources\AssignmentResource;
use App\Models\AssignmentSubmission;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\RepeatableEntry;
use Filament\Schemas\Components\Grid;

class ViewAssignment extends ViewRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->submissions()->count() === 0),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Assignment Details')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('subject.name')->label('Subject'),
                            TextEntry::make('classroom.name')->label('Class'),
                            TextEntry::make('week_number')->label('Week')->badge(),
                            TextEntry::make('title')->label('Title')->columnSpan(2),
                            TextEntry::make('is_active')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                                ->color(fn ($state) => $state ? 'success' : 'danger'),
                        ]),
                        TextEntry::make('description')
                            ->label('Instructions')
                            ->columnSpanFull()
                            ->visible(fn ($record) => filled($record->description)),
                    ]),

                Section::make('Questions')
                    ->schema([
                        RepeatableEntry::make('questions')
                            ->schema([
                                TextEntry::make('question_text')
                                    ->label('Question')
                                    ->columnSpanFull(),
                                Grid::make(2)->schema([
                                    TextEntry::make('options')
                                        ->label('Options')
                                        ->formatStateUsing(function ($state) {
                                            $options = [];
                                            foreach ($state as $key => $val) {
                                                $options[] = "<strong>{$key}:</strong> {$val}";
                                            }
                                            return implode('<br>', $options);
                                        })
                                        ->html(),
                                    TextEntry::make('correct_option')
                                        ->label('Correct Answer')
                                        ->badge()
                                        ->color('success'),
                                ]),
                                TextEntry::make('points')->label('Points')->badge(),
                            ])
                            ->columns(1),
                    ])
                    ->collapsible(),
            ]);
    }
}
