<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Filament\Resources\DepartmentResource\RelationManagers;
use App\Models\ClassArm;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Session;
use App\Models\StudentEnrollment;
use App\Services\FrontendLibrary;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Departments';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Department Name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->placeholder('e.g. Science, Commercial, Arts / Humanities, Technical')
                ->helperText('Name of the academic stream or department.'),

            TextInput::make('code')
                ->label('Department Code')
                ->maxLength(50)
                ->placeholder('e.g. SCI, COMM, ART')
                ->helperText('Short abbreviation or code for reporting.'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive departments are hidden from new student enrollment options.'),

            Textarea::make('description')
                ->label('Description')
                ->rows(2)
                ->placeholder('Optional summary of subjects or focus for this stream...')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Department')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Enrolled Students')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                static::makeAssignStudentsAction(
                    name: 'assign_students',
                    label: 'Assign Students',
                    resolvePresetDepartment: fn (Department $record): Department => $record,
                ),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit'   => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return FrontendLibrary::getBooleanSetting('enable_departments', false) && (Auth::user()?->canAccessAdminPage('departments') ?? false);
    }

    /**
     * Build a reusable Action for bulk-assigning senior students to a department.
     */
    public static function makeAssignStudentsAction(
        string $name = 'assign_students',
        string $label = 'Assign Students',
        ?Closure $resolvePresetDepartment = null,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-o-user-plus')
            ->color('success')
            ->visible(fn (): bool => Auth::user()?->canPerformAdminAction('departments', 'update') ?? false)
            ->modalHeading(function (?Department $record = null) use ($resolvePresetDepartment): string {
                $preset = $resolvePresetDepartment ? $resolvePresetDepartment($record) : null;

                return $preset
                    ? "Assign Students to {$preset->name} Department"
                    : 'Bulk Assign Students to Department';
            })
            ->modalDescription('Select a senior classroom and check multiple students to assign them to this department at once.')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Assign Selected Students')
            ->form(function (?Department $record = null) use ($resolvePresetDepartment): array {
                $preset = $resolvePresetDepartment ? $resolvePresetDepartment($record) : null;

                return static::assignStudentsFormSchema($preset);
            })
            ->action(function (array $data, ?Department $record = null) use ($resolvePresetDepartment): void {
                $preset = $resolvePresetDepartment ? $resolvePresetDepartment($record) : null;
                static::handleAssignStudents($data, $preset);
            });
    }

    /**
     * Form schema for bulk-assigning students from a senior classroom into a department.
     */
    public static function assignStudentsFormSchema(?Department $presetDepartment = null): array
    {
        $hasDepartmentClassrooms = Classroom::query()->where('has_departments', true)->exists();

        return [
            Placeholder::make('no_department_classrooms_notice')
                ->label('Setup Required')
                ->content('No classrooms have "Senior School (Has Departments)" enabled yet. Enable it on your senior classrooms (e.g., SS 1–SS 3) under Academic Management → Classrooms first.')
                ->visible(! $hasDepartmentClassrooms),

            Select::make('session_id')
                ->label('Academic Session')
                ->options(fn () => Session::query()
                    ->orderByDesc('is_active')
                    ->orderByDesc('start_year')
                    ->pluck('name', 'id'))
                ->default(fn () => Session::where('is_active', true)->value('id'))
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('student_ids', [])),

            Select::make('classroom_id')
                ->label('Senior Classroom')
                ->options(fn () => Classroom::query()
                    ->where('has_departments', true)
                    ->orderBy('class_order')
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (callable $set): void {
                    $set('class_arm_id', null);
                    $set('student_ids', []);
                })
                ->helperText('Only classrooms with "Senior School (Has Departments)" enabled are listed.'),

            Select::make('class_arm_id')
                ->label('Filter by Class Arm (Optional)')
                ->options(function (callable $get): array {
                    $classroomId = $get('classroom_id');
                    if (! $classroomId) {
                        return [];
                    }

                    return ClassArm::query()
                        ->where('classroom_id', $classroomId)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->placeholder('All arms')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('student_ids', []))
                ->visible(function (callable $get): bool {
                    $classroomId = $get('classroom_id');

                    return FrontendLibrary::getBooleanSetting('enable_class_arms', false)
                        && filled($classroomId)
                        && ClassArm::where('classroom_id', $classroomId)->exists();
                }),

            Select::make('department_id')
                ->label('Target Department')
                ->options(function () use ($presetDepartment): array {
                    return Department::query()
                        ->where(function (Builder $query) use ($presetDepartment): void {
                            $query->where('is_active', true);
                            if ($presetDepartment) {
                                $query->orWhere('id', $presetDepartment->id);
                            }
                        })
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->default($presetDepartment?->id)
                ->disabled($presetDepartment !== null)
                ->dehydrated()
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('student_ids', [])),

            Toggle::make('only_unassigned')
                ->label('Show only students without a department')
                ->default(false)
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('student_ids', []))
                ->visible(fn (callable $get): bool => filled($get('classroom_id')) && filled($get('session_id'))),

            Placeholder::make('eligible_summary')
                ->label('Class Roster Status')
                ->content(function (callable $get) use ($presetDepartment): string {
                    $sessionId = (int) ($get('session_id') ?? 0);
                    $classroomId = (int) ($get('classroom_id') ?? 0);
                    $departmentId = (int) ($get('department_id') ?? $presetDepartment?->id ?? 0);
                    $classArmId = filled($get('class_arm_id')) ? (int) $get('class_arm_id') : null;
                    $onlyUnassigned = (bool) ($get('only_unassigned') ?? false);

                    if (! $sessionId || ! $classroomId || ! $departmentId) {
                        return 'Select a session, senior classroom, and target department to load students.';
                    }

                    $eligibleCount = static::getEligibleDepartmentEnrollmentsQuery(
                        $sessionId,
                        $classroomId,
                        $departmentId,
                        $onlyUnassigned,
                        $classArmId,
                    )->count();

                    $alreadyInDeptCount = StudentEnrollment::query()
                        ->where('session_id', $sessionId)
                        ->where('classroom_id', $classroomId)
                        ->when($classArmId, fn (Builder $q) => $q->where('class_arm_id', $classArmId))
                        ->where('department_id', $departmentId)
                        ->whereHas('student')
                        ->count();

                    if ($eligibleCount === 0) {
                        return "No eligible students found ({$alreadyInDeptCount} already in this department).";
                    }

                    return "{$eligibleCount} eligible student(s) listed below ({$alreadyInDeptCount} already in this department).";
                })
                ->visible(fn (callable $get): bool => filled($get('classroom_id')) && filled($get('session_id')) && filled($get('department_id') ?? $presetDepartment?->id)),

            CheckboxList::make('student_ids')
                ->label('Select Students to Assign to Department')
                ->options(function (callable $get) use ($presetDepartment): array {
                    $sessionId = (int) ($get('session_id') ?? 0);
                    $classroomId = (int) ($get('classroom_id') ?? 0);
                    $departmentId = (int) ($get('department_id') ?? $presetDepartment?->id ?? 0);
                    $classArmId = filled($get('class_arm_id')) ? (int) $get('class_arm_id') : null;
                    $onlyUnassigned = (bool) ($get('only_unassigned') ?? false);

                    if (! $sessionId || ! $classroomId || ! $departmentId) {
                        return [];
                    }

                    return static::getEligibleDepartmentEnrollments(
                        $sessionId,
                        $classroomId,
                        $departmentId,
                        $onlyUnassigned,
                        $classArmId,
                    )
                        ->mapWithKeys(fn (StudentEnrollment $enrollment): array => [
                            $enrollment->student_id => $enrollment->student?->full_name ?: ('Student #' . $enrollment->student_id),
                        ])
                        ->all();
                })
                ->descriptions(function (callable $get) use ($presetDepartment): array {
                    $sessionId = (int) ($get('session_id') ?? 0);
                    $classroomId = (int) ($get('classroom_id') ?? 0);
                    $departmentId = (int) ($get('department_id') ?? $presetDepartment?->id ?? 0);
                    $classArmId = filled($get('class_arm_id')) ? (int) $get('class_arm_id') : null;
                    $onlyUnassigned = (bool) ($get('only_unassigned') ?? false);

                    if (! $sessionId || ! $classroomId || ! $departmentId) {
                        return [];
                    }

                    return static::getEligibleDepartmentEnrollments(
                        $sessionId,
                        $classroomId,
                        $departmentId,
                        $onlyUnassigned,
                        $classArmId,
                    )
                        ->mapWithKeys(function (StudentEnrollment $enrollment): array {
                            $adm = $enrollment->student?->admission_number ?: 'No Adm No';
                            $armPart = $enrollment->classArm ? " • {$enrollment->classArm->name}" : '';
                            $deptLabel = $enrollment->department
                                ? "Current Dept: {$enrollment->department->name}"
                                : 'Unassigned';

                            return [$enrollment->student_id => "{$adm}{$armPart} • {$deptLabel}"];
                        })
                        ->all();
                })
                ->searchable()
                ->bulkToggleable()
                ->columns(2)
                ->required()
                ->minItems(1)
                ->visible(fn (callable $get): bool => filled($get('classroom_id')) && filled($get('session_id')) && filled($get('department_id') ?? $presetDepartment?->id))
                ->helperText('Select multiple students (or click Select all) and submit to assign them to this department at once. Existing students in the department remain assigned.'),
        ];
    }

    /**
     * Persist bulk department assignment for selected senior students.
     */
    public static function handleAssignStudents(array $data, ?Department $presetDepartment = null): int
    {
        abort_unless(Auth::user()?->canPerformAdminAction('departments', 'update') ?? false, 403);

        $sessionId = (int) ($data['session_id'] ?? 0);
        $classroomId = (int) ($data['classroom_id'] ?? 0);
        $departmentId = (int) ($presetDepartment?->id ?? $data['department_id'] ?? 0);
        $studentIds = array_values(array_unique(array_map('intval', $data['student_ids'] ?? [])));

        $classroom = Classroom::query()
            ->where('id', $classroomId)
            ->where('has_departments', true)
            ->firstOrFail();

        $department = Department::query()->findOrFail($departmentId);

        if (empty($studentIds)) {
            Notification::make()
                ->title('No Students Selected')
                ->body('Please select at least one student to assign to this department.')
                ->warning()
                ->send();

            return 0;
        }

        $updatedCount = DB::transaction(fn (): int => StudentEnrollment::query()
            ->where('session_id', $sessionId)
            ->where('classroom_id', $classroom->id)
            ->whereIn('student_id', $studentIds)
            ->whereHas('student')
            ->update(['department_id' => $department->id]));

        Notification::make()
            ->title('Students Assigned to Department')
            ->body("Assigned {$updatedCount} {$classroom->name} student(s) to {$department->name}.")
            ->success()
            ->send();

        return $updatedCount;
    }

    protected static function getEligibleDepartmentEnrollmentsQuery(
        int $sessionId,
        int $classroomId,
        int $targetDepartmentId,
        bool $onlyUnassigned,
        ?int $classArmId = null,
    ): Builder {
        return StudentEnrollment::query()
            ->where('session_id', $sessionId)
            ->where('classroom_id', $classroomId)
            ->when($classArmId, fn (Builder $query) => $query->where('class_arm_id', $classArmId))
            ->whereHas('student')
            ->when(
                $onlyUnassigned,
                fn (Builder $query) => $query->whereNull('department_id'),
                fn (Builder $query) => $query->where(function (Builder $sub) use ($targetDepartmentId): void {
                    $sub->whereNull('department_id')
                        ->orWhere('department_id', '!=', $targetDepartmentId);
                })
            );
    }

    protected static function getEligibleDepartmentEnrollments(
        int $sessionId,
        int $classroomId,
        int $targetDepartmentId,
        bool $onlyUnassigned,
        ?int $classArmId = null,
    ): Collection {
        return static::getEligibleDepartmentEnrollmentsQuery(
            $sessionId,
            $classroomId,
            $targetDepartmentId,
            $onlyUnassigned,
            $classArmId,
        )
            ->with(['student.user', 'department', 'classArm'])
            ->get()
            ->sortBy(fn (StudentEnrollment $enrollment): string => strtolower($enrollment->student?->full_name ?? ''))
            ->values();
    }
}
