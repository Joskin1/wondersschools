<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\Score;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentScoresWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $user = auth()->user();
        
        if (!$user->isTeacher() || !$user->staff) {
            return $table->query(Score::query()->whereRaw('1 = 0'));
        }

        $session = now()->year . '/' . (now()->year + 1);
        
        // Get subject IDs where teacher has assignments
        $subjectIds = \DB::table('classroom_subject_teacher')
            ->where('staff_id', $user->staff->id)
            ->where('session', $session)
            ->pluck('subject_id');

        return $table
            ->query(
                Score::query()
                    ->whereIn('subject_id', $subjectIds)
                    ->where('session', $session)
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($record) => $record->student->first_name . ' ' . $record->student->last_name),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject'),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Classroom'),
                Tables\Columns\TextColumn::make('scoreHeader.name')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Score')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Entered')
                    ->dateTime()
                    ->since(),
            ])
            ->heading('Recent Score Entries')
            ->description('Latest 10 scores entered for your subjects');
    }
}
