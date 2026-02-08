<?php

namespace App\Filament\Teacher\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MyAssignmentsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';
    
    protected string $view = 'filament.teacher.pages.my-assignments-page';
    
    protected static ?string $navigationLabel = 'My Assignments';
    
    protected static ?string $title = 'My Teaching Assignments';
    
    protected static string|\UnitEnum|null $navigationGroup = 'My Assignments';
    
    protected static ?int $navigationSort = 1;

    public ?string $session = null;

    public function mount(): void
    {
        // Set default session to current academic year
        $this->session = now()->year . '/' . (now()->year + 1);
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        
        if (!$user->isTeacher() || !$user->staff) {
            return $table->query(
                DB::table('classroom_subject_teacher')->whereRaw('1 = 0')
            );
        }

        return $table
            ->query(
                DB::table('classroom_subject_teacher')
                    ->join('classrooms', 'classroom_subject_teacher.classroom_id', '=', 'classrooms.id')
                    ->join('subjects', 'classroom_subject_teacher.subject_id', '=', 'subjects.id')
                    ->where('classroom_subject_teacher.staff_id', $user->staff->id)
                    ->where('classroom_subject_teacher.session', $this->session)
                    ->select(
                        'classroom_subject_teacher.id',
                        'classrooms.name as classroom_name',
                        'subjects.name as subject_name',
                        'classroom_subject_teacher.session',
                        'classroom_subject_teacher.created_at'
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('classroom_name')
                    ->label('Classroom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject_name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('session')
                    ->label('Session')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Assigned On')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->heading('My Teaching Assignments for ' . $this->session)
            ->description('These are the classrooms and subjects you are assigned to teach.')
            ->paginated([10, 25, 50]);
    }
}
