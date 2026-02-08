<?php

namespace App\Filament\Student\Widgets;

use App\Models\Score;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentScoresWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $student = auth('student')->user();
        
        if (!$student) {
            return $table->query(Score::query()->whereRaw('1 = 0'));
        }

        return $table
            ->query(
                Score::query()
                    ->where('student_id', $student->id)
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject'),
                Tables\Columns\TextColumn::make('scoreHeader.name')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Score')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color(fn (Score $record): string => match (true) {
                        $record->scoreHeader && ($record->value / $record->scoreHeader->max_score) >= 0.7 => 'success',
                        $record->scoreHeader && ($record->value / $record->scoreHeader->max_score) >= 0.5 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime()
                    ->since(),
            ])
            ->heading('Recent Scores')
            ->description('Your latest 5 score entries');
    }
}
