<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassTeacherAssignmentResource\Pages;
use App\Models\ClassTeacherAssignment;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\User;
use Closure;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassTeacherAssignmentResource extends Resource
{
    protected static ?string $model = ClassTeacherAssignment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Class Teachers';

    protected static ?int $navigationSort = 8;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminPage('class_teachers') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_id')
                    ->label('Teacher')
                    ->options(fn () => User::assignableTeachers()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (User $user) => [
                            $user->id => $user->name . ($user->isAdmin() ? ' (Admin)' : ''),
                        ])
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Active teachers and administrators can be assigned')
                    ->rules([
                        fn (\Filament\Schemas\Components\Utilities\Get $get, ?\Illuminate\Database\Eloquent\Model $record = null): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                            $sessionId = $get('session_id');
                            if (! $value || ! $sessionId) {
                                return;
                            }

                            $existing = ClassTeacherAssignment::where('teacher_id', $value)
                                ->where('session_id', $sessionId)
                                ->with('classroom')
                                ->first();

                            if (! $existing) {
                                return;
                            }

                            // When editing, ignore the current record
                            $recordId = $record?->id ?? request()->route('record');
                            if ($recordId && (int) $existing->id === (int) $recordId) {
                                return;
                            }

                            $teacherName = User::find($value)?->name ?? 'This teacher';
                            $className = $existing->classroom?->name ?? 'another class';

                            $fail("{$teacherName} is already assigned as class teacher for {$className} in the selected session.");
                        },
                    ]),

                Select::make('class_id')
                    ->label('Class')
                    ->options(Classroom::active()->ordered()->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->searchable()
                    ->preload()
                    ->rules([
                        fn (\Filament\Schemas\Components\Utilities\Get $get, ?\Illuminate\Database\Eloquent\Model $record = null): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                            $sessionId = $get('session_id');
                            if (! $value || ! $sessionId) {
                                return;
                            }

                            $query = ClassTeacherAssignment::where('class_id', $value)
                                ->where('session_id', $sessionId);

                            $deptId = $get('department_id');
                            $armId  = $get('class_arm_id');

                            if ($deptId) {
                                $query->where('department_id', $deptId);
                            } elseif ($armId) {
                                $query->where('class_arm_id', $armId);
                            } else {
                                $query->whereNull('department_id')->whereNull('class_arm_id');
                            }

                            $existing = $query->with('teacher')->first();

                            if (! $existing) {
                                return;
                            }

                            // When editing, ignore the current record
                            $recordId = $record?->id ?? request()->route('record');
                            if ($recordId && (int) $existing->id === (int) $recordId) {
                                return;
                            }

                            $className = Classroom::find($value)?->name ?? 'This class';
                            $teacherName = $existing->teacher?->name ?? 'another teacher';

                            $divisionInfo = $deptId ? ' (Department)' : ($armId ? ' (Arm)' : '');
                            $fail("{$className}{$divisionInfo} already has a class teacher ({$teacherName}) for the selected session.");
                        },
                    ]),

                Select::make('department_id')
                    ->label('Department')
                    ->options(\App\Models\Department::active()->pluck('name', 'id'))
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) Classroom::find($get('class_id'))?->has_departments)
                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) Classroom::find($get('class_id'))?->has_departments)
                    ->searchable()
                    ->preload()
                    ->helperText('Assign homeroom class teacher for this senior department (e.g. SS 2 Science)'),

                Select::make('class_arm_id')
                    ->label('Class Arm')
                    ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => \App\Models\ClassArm::where('classroom_id', $get('class_id'))->active()->pluck('name', 'id'))
                    ->visible(function (\Filament\Schemas\Components\Utilities\Get $get) {
                        $classroom = Classroom::find($get('class_id'));
                        return $classroom && !$classroom->has_departments && $classroom->hasArms();
                    })
                    ->required(function (\Filament\Schemas\Components\Utilities\Get $get) {
                        $classroom = Classroom::find($get('class_id'));
                        return $classroom && !$classroom->has_departments && $classroom->hasArms();
                    })
                    ->searchable()
                    ->preload()
                    ->helperText('Assign homeroom class teacher for this arm (e.g. JSS 1 Gold)'),

                Select::make('session_id')
                    ->label('Academic Session')
                    ->options(Session::orderBy('start_year', 'desc')->pluck('name', 'id'))
                    ->required()
                    ->default(fn () => Session::active()->first()?->id)
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('teacher_avatar')
                    ->label('')
                    ->circular()
                    ->getStateUsing(function (ClassTeacherAssignment $record): string {
                        $avatarPath = $record->teacher?->teacher?->profile_picture;
                        if ($avatarPath) {
                            return \Illuminate\Support\Facades\Storage::disk(config('filesystems.upload_disk', 'public'))->url($avatarPath);
                        }
                        return 'https://ui-avatars.com/api/?name=' . urlencode($record->teacher?->name ?? '?') . '&background=6366f1&color=fff&size=40';
                    })
                    ->size(40)
                    ->grow(false),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('division')
                    ->label('Arm / Department')
                    ->getStateUsing(function (ClassTeacherAssignment $record): string {
                        if ($record->department) {
                            return 'Dept: ' . $record->department->name;
                        }
                        if ($record->classArm) {
                            return 'Arm: ' . $record->classArm->name;
                        }
                        return 'Entire Class';
                    })
                    ->badge()
                    ->color(fn (ClassTeacherAssignment $record): string => $record->department ? 'purple' : ($record->classArm ? 'info' : 'gray')),

                Tables\Columns\TextColumn::make('session.name')
                    ->label('Session')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Assigned On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session_id')
                    ->label('Session')
                    ->options(Session::orderBy('start_year', 'desc')->pluck('name', 'id'))
                    ->default(Session::active()->first()?->id),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Teacher')
                    ->options(fn () => User::assignableTeachers()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (User $user) => [
                            $user->id => $user->name . ($user->isAdmin() ? ' (Admin)' : ''),
                        ])
                    )
                    ->searchable(),
            ])
            ->actions([
                \STS\FilamentImpersonate\Actions\Impersonate::make()
                    ->impersonateRecord(fn ($record) => $record->teacher)
                    ->redirectTo('/teacher'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - preserve data integrity
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassTeacherAssignments::route('/'),
            'create' => Pages\CreateClassTeacherAssignment::route('/create'),
            'edit' => Pages\EditClassTeacherAssignment::route('/{record}/edit'),
        ];
    }
}
