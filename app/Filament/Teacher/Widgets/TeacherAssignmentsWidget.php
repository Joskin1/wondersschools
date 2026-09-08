<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\TeacherSubjectAssignment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TeacherAssignmentsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'My Teaching Assignments';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return TeacherSubjectAssignment::query()
            ->where('teacher_id', auth()->id())
            ->active()
            ->with(['subject', 'classroom']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Class')
                    ->sortable(),
            ])
            ->paginated(false)
            ->defaultSort('classroom.name');
    }
}
