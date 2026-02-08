<?php

namespace App\Filament\Student\Pages;

use App\Models\Subject;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class MySubjectsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';
    
    protected string $view = 'filament.student.pages.my-subjects-page';
    
    protected static ?string $navigationLabel = 'My Subjects';
    
    protected static ?string $title = 'My Subjects';
    
    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        $student = auth('student')->user();
        
        if (!$student) {
            return $table->query(Subject::query()->whereRaw('1 = 0'));
        }

        // Get student's current classroom
        $classroom = $student->classrooms()->first();
        
        if (!$classroom) {
            return $table->query(Subject::query()->whereRaw('1 = 0'));
        }

        return $table
            ->query(
                Subject::query()
                    ->whereHas('classrooms', function (Builder $query) use ($classroom) {
                        $query->where('classrooms.id', $classroom->id);
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->heading('My Subjects for ' . ($classroom->name ?? 'Unknown Classroom'))
            ->description('All subjects in your current classroom.')
            ->defaultSort('name')
            ->paginated([10, 25, 50]);
    }
}
