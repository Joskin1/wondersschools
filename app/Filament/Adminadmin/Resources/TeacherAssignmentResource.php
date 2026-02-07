<?php

namespace App\Filament\Adminadmin\Resources;

use App\Services\TeacherAssignmentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TeacherAssignmentResource extends Resource
{
    protected static ?string $model = null; // Using pivot table directly
    
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    
    protected static string|\UnitEnum|null $navigationGroup = 'Administration';
    
    protected static ?string $navigationLabel = 'Teacher Assignments';
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment Details')
                    ->schema([
                        Forms\Components\Select::make('staff_id')
                            ->label('Teacher')
                            ->relationship('staff', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('classroom_id')
                            ->label('Classroom')
                            ->relationship('classroom', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\TextInput::make('session')
                            ->required()
                            ->default(fn() => now()->year . '/' . (now()->year + 1))
                            ->helperText('Format: 2024/2025'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                DB::table('classroom_subject_teacher')
                    ->join('staff', 'classroom_subject_teacher.staff_id', '=', 'staff.id')
                    ->join('users', 'staff.user_id', '=', 'users.id')
                    ->join('classrooms', 'classroom_subject_teacher.classroom_id', '=', 'classrooms.id')
                    ->join('subjects', 'classroom_subject_teacher.subject_id', '=', 'subjects.id')
                    ->select(
                        'classroom_subject_teacher.id',
                        'classroom_subject_teacher.staff_id',
                        'classroom_subject_teacher.classroom_id',
                        'classroom_subject_teacher.subject_id',
                        'classroom_subject_teacher.session',
                        'users.name as teacher_name',
                        'classrooms.name as classroom_name',
                        'subjects.name as subject_name'
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('teacher_name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('classroom_name')
                    ->label('Classroom')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subject_name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('session')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session')
                    ->options(function () {
                        return DB::table('classroom_subject_teacher')
                            ->distinct()
                            ->pluck('session', 'session')
                            ->toArray();
                    }),
                
                Tables\Filters\SelectFilter::make('staff_id')
                    ->label('Teacher')
                    ->options(function () {
                        return DB::table('staff')
                            ->join('users', 'staff.user_id', '=', 'users.id')
                            ->pluck('users.name', 'staff.id')
                            ->toArray();
                    })
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        DB::table('classroom_subject_teacher')
                            ->where('id', $record->id)
                            ->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('delete')
                    ->label('Delete selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $ids = $records->pluck('id')->toArray();
                        DB::table('classroom_subject_teacher')
                            ->whereIn('id', $ids)
                            ->delete();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulk_assign')
                    ->label('Bulk Assign')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('staff_id')
                            ->label('Teacher')
                            ->options(function () {
                                return DB::table('staff')
                                    ->join('users', 'staff.user_id', '=', 'users.id')
                                    ->pluck('users.name', 'staff.id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\Select::make('classrooms')
                            ->label('Classrooms')
                            ->multiple()
                            ->options(function () {
                                return DB::table('classrooms')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\Select::make('subjects')
                            ->label('Subjects')
                            ->multiple()
                            ->options(function () {
                                return DB::table('subjects')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\TextInput::make('session')
                            ->required()
                            ->default(fn() => now()->year . '/' . (now()->year + 1)),
                    ])
                    ->action(function (array $data) {
                        $service = app(TeacherAssignmentService::class);
                        $assignments = [];
                        
                        foreach ($data['classrooms'] as $classroomId) {
                            foreach ($data['subjects'] as $subjectId) {
                                $assignments[] = [
                                    'classroom_id' => $classroomId,
                                    'subject_id' => $subjectId,
                                ];
                            }
                        }
                        
                        $service->bulkAssign($data['staff_id'], $assignments, $data['session']);
                    })
                    ->successNotificationTitle('Assignments created successfully'),
            ])
            ->defaultSort('session', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Adminadmin\Resources\TeacherAssignmentResource\Pages\ManageTeacherAssignments::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Use bulk assign action instead
    }
}
