<?php

namespace App\Filament\Student\Resources;

use App\Filament\Student\Resources\StudentAssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentAssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Assignments';

    protected static ?string $modelLabel = 'Assignment';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $student = auth()->user()?->student;
        if (! $student) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $enrollment = $student->currentEnrollment();
        if (! $enrollment) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $classroomId = $enrollment->classroom_id;
        $subjectIds = $enrollment->classroom->subjects()->pluck('subjects.id')->toArray();

        return parent::getEloquentQuery()
            ->where('classroom_id', $classroomId)
            ->whereIn('subject_id', $subjectIds)
            ->where('is_active', true)
            ->active(); // Using the scopeActive for session/term
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('week_number')
                    ->label('Week')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => "Week {$state}"),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        $studentId = auth()->user()?->student?->id;
                        return $record->submissions()->where('student_id', $studentId)->exists() 
                            ? 'Completed' 
                            : 'Pending';
                    })
                    ->color(function ($record) {
                        $studentId = auth()->user()?->student?->id;
                        return $record->submissions()->where('student_id', $studentId)->exists() 
                            ? 'success' 
                            : 'warning';
                    }),
                    
                Tables\Columns\TextColumn::make('score')
                    ->label('Your Score')
                    ->formatStateUsing(function ($record) {
                        $studentId = auth()->user()?->student?->id;
                        $submission = $record->submissions()->where('student_id', $studentId)->first();
                        
                        if (!$submission) {
                            return '-';
                        }
                        
                        return "{$submission->score} / {$submission->total_points} ({$submission->percentageScore()}%)";
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name'),
                    
                Tables\Filters\SelectFilter::make('week_number')
                    ->label('Week')
                    ->options(array_combine(range(1, 12), array_map(fn ($w) => "Week {$w}", range(1, 12)))),
            ])
            ->actions([
                Tables\Actions\Action::make('take_quiz')
                    ->label('Take Quiz')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (Assignment $record): string => static::getUrl('take', ['record' => $record]))
                    ->visible(function (Assignment $record) {
                        $studentId = auth()->user()?->student?->id;
                        return !$record->submissions()->where('student_id', $studentId)->exists();
                    }),
                    
                Tables\Actions\Action::make('view_result')
                    ->label('View Result')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->url(fn (Assignment $record): string => static::getUrl('result', ['record' => $record]))
                    ->visible(function (Assignment $record) {
                        $studentId = auth()->user()?->student?->id;
                        return $record->submissions()->where('student_id', $studentId)->exists();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentAssignments::route('/'),
            'take' => Pages\TakeAssignment::route('/{record}/take'),
            'result' => Pages\ViewAssignmentResult::route('/{record}/result'),
        ];
    }
}
