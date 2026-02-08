<?php

namespace App\Filament\Teacher\Pages;

use App\Models\Student;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class MyStudentsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';
    
    protected string $view = 'filament.teacher.pages.my-students-page';
    
    protected static ?string $navigationLabel = 'My Students';
    
    protected static ?string $title = 'My Students';
    
    protected static string|\UnitEnum|null $navigationGroup = 'My Assignments';
    
    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        $user = auth()->user();
        
        if (!$user->isTeacher() || !$user->staff) {
            return $table->query(Student::query()->whereRaw('1 = 0'));
        }

        // Get classrooms where the teacher has assignments
        $classroomIds = \DB::table('classroom_subject_teacher')
            ->where('staff_id', $user->staff->id)
            ->where('session', now()->year . '/' . (now()->year + 1))
            ->distinct()
            ->pluck('classroom_id');

        return $table
            ->query(
                Student::query()
                    ->whereHas('classrooms', function (Builder $query) use ($classroomIds) {
                        $query->whereIn('classrooms.id', $classroomIds);
                    })
                    ->with('classrooms')
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('admission_number')
                    ->label('Admission No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('classrooms.name')
                    ->label('Classroom')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Enrolled On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->heading('Students in My Classrooms')
            ->description('All students enrolled in classrooms where you have teaching assignments.')
            ->defaultSort('first_name')
            ->paginated([10, 25, 50, 100]);
    }
}
