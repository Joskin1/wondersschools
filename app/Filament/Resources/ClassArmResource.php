<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassArmResource\Pages;
use App\Filament\Resources\ClassArmResource\RelationManagers;
use App\Models\ClassArm;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\StudentEnrollment;
use App\Services\FrontendLibrary;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassArmResource extends Resource
{
    protected static ?string $model = ClassArm::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Class Arms';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return FrontendLibrary::getBooleanSetting('enable_class_arms');
    }

    public static function canAccess(): bool
    {
        return FrontendLibrary::getBooleanSetting('enable_class_arms') && (auth()->user()?->canAccessAdminPage('class_arms') ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('classroom_id')
                    ->label('Classroom')
                    ->relationship('classroom', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->placeholder('Select classroom to add arm against')
                    ->helperText('Select the classroom (e.g. JSS 1) that this arm belongs to.'),

                TextInput::make('name')
                    ->label('Arm Name')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('e.g., Gold, Silver, Diamond, A, B, Science')
                    ->helperText('Name/stream identifier for this arm (e.g., Gold, Silver, A, B).'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive arms will not be selectable for new enrollments.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('classroom.name')
                    ->label('Classroom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Arm')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('classroom.classGroup.name')
                    ->label('Class Group')
                    ->badge()
                    ->color('gray')
                    ->placeholder('None'),

                TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Students')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('classroom_id')
                    ->label('Filter by Classroom')
                    ->relationship('classroom', 'name'),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Action::make('assign_students')
                    ->label('Assign Students')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false)
                    ->modalHeading(fn (ClassArm $record): string => "Assign Students to {$record->full_name}")
                    ->modalDescription('Select one or more students from this classroom to add them to this arm at once.')
                    ->modalWidth('4xl')
                    ->modalSubmitActionLabel('Assign Selected Students')
                    ->form(fn (ClassArm $record): array => static::assignStudentsFormSchema($record))
                    ->action(fn (ClassArm $record, array $data) => static::handleAssignStudents($data, $record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('classroom_id', 'asc');
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
            'index' => Pages\ListClassArms::route('/'),
            'create' => Pages\CreateClassArm::route('/create'),
            'edit' => Pages\EditClassArm::route('/{record}/edit'),
        ];
    }

    /**
     * Form schema for bulk-assigning students from a classroom into a class arm.
     */
    public static function assignStudentsFormSchema(?ClassArm $presetArm = null): array
    {
        return [
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
                ->label('Classroom')
                ->options(fn () => Classroom::query()
                    ->orderBy('class_order')
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->default($presetArm?->classroom_id)
                ->disabled($presetArm !== null)
                ->dehydrated()
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (callable $set) use ($presetArm): void {
                    if (! $presetArm) {
                        $set('class_arm_id', null);
                    }
                    $set('student_ids', []);
                }),

            Select::make('class_arm_id')
                ->label('Target Class Arm')
                ->options(function (callable $get) use ($presetArm): array {
                    $classroomId = $get('classroom_id') ?? $presetArm?->classroom_id;
                    if (! $classroomId) {
                        return [];
                    }

                    return ClassArm::query()
                        ->where('classroom_id', $classroomId)
                        ->where(function (Builder $query) use ($presetArm): void {
                            $query->where('is_active', true);
                            if ($presetArm) {
                                $query->orWhere('id', $presetArm->id);
                            }
                        })
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->default($presetArm?->id)
                ->disabled($presetArm !== null)
                ->dehydrated()
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('student_ids', []))
                ->helperText('Choose the arm to assign students into (create an arm for the class first if none appears).'),

            Toggle::make('only_unassigned')
                ->label('Show only students without an arm')
                ->default(false)
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('student_ids', []))
                ->visible(fn (callable $get): bool => filled($get('classroom_id') ?? $presetArm?->classroom_id) && filled($get('session_id'))),

            Placeholder::make('eligible_summary')
                ->label('Class Roster Status')
                ->content(function (callable $get) use ($presetArm): string {
                    $sessionId = (int) ($get('session_id') ?? 0);
                    $classroomId = (int) ($get('classroom_id') ?? $presetArm?->classroom_id ?? 0);
                    $armId = (int) ($get('class_arm_id') ?? $presetArm?->id ?? 0);
                    $onlyUnassigned = (bool) ($get('only_unassigned') ?? false);

                    if (! $sessionId || ! $classroomId || ! $armId) {
                        return 'Select a session, classroom, and target class arm to load students.';
                    }

                    $eligibleCount = static::getEligibleArmEnrollmentsQuery($sessionId, $classroomId, $armId, $onlyUnassigned)->count();
                    $alreadyInArmCount = StudentEnrollment::query()
                        ->where('session_id', $sessionId)
                        ->where('classroom_id', $classroomId)
                        ->where('class_arm_id', $armId)
                        ->whereHas('student')
                        ->count();

                    if ($eligibleCount === 0) {
                        return "No eligible students found ({$alreadyInArmCount} already in this arm).";
                    }

                    return "{$eligibleCount} eligible student(s) listed below ({$alreadyInArmCount} already in this arm).";
                })
                ->visible(fn (callable $get): bool => filled($get('classroom_id') ?? $presetArm?->classroom_id) && filled($get('session_id')) && filled($get('class_arm_id') ?? $presetArm?->id)),

            CheckboxList::make('student_ids')
                ->label('Select Students to Add to Arm')
                ->options(function (callable $get) use ($presetArm): array {
                    $sessionId = (int) ($get('session_id') ?? 0);
                    $classroomId = (int) ($get('classroom_id') ?? $presetArm?->classroom_id ?? 0);
                    $armId = (int) ($get('class_arm_id') ?? $presetArm?->id ?? 0);
                    $onlyUnassigned = (bool) ($get('only_unassigned') ?? false);

                    if (! $sessionId || ! $classroomId || ! $armId) {
                        return [];
                    }

                    return static::getEligibleArmEnrollments($sessionId, $classroomId, $armId, $onlyUnassigned)
                        ->mapWithKeys(fn (StudentEnrollment $enrollment): array => [
                            $enrollment->student_id => $enrollment->student?->full_name ?: ('Student #' . $enrollment->student_id),
                        ])
                        ->all();
                })
                ->descriptions(function (callable $get) use ($presetArm): array {
                    $sessionId = (int) ($get('session_id') ?? 0);
                    $classroomId = (int) ($get('classroom_id') ?? $presetArm?->classroom_id ?? 0);
                    $armId = (int) ($get('class_arm_id') ?? $presetArm?->id ?? 0);
                    $onlyUnassigned = (bool) ($get('only_unassigned') ?? false);

                    if (! $sessionId || ! $classroomId || ! $armId) {
                        return [];
                    }

                    return static::getEligibleArmEnrollments($sessionId, $classroomId, $armId, $onlyUnassigned)
                        ->mapWithKeys(function (StudentEnrollment $enrollment): array {
                            $adm = $enrollment->student?->admission_number ?: 'No Adm No';
                            $armLabel = $enrollment->classArm
                                ? "Current Arm: {$enrollment->classArm->name}"
                                : 'Unassigned';

                            return [$enrollment->student_id => "{$adm} • {$armLabel}"];
                        })
                        ->all();
                })
                ->searchable()
                ->bulkToggleable()
                ->columns(2)
                ->required()
                ->minItems(1)
                ->visible(fn (callable $get): bool => filled($get('classroom_id') ?? $presetArm?->classroom_id) && filled($get('session_id')) && filled($get('class_arm_id') ?? $presetArm?->id))
                ->helperText('Select multiple students (or click Select all) and submit to add them all to this arm at once. Existing students in the arm are preserved.'),
        ];
    }

    /**
     * Persist bulk class-arm assignment for selected enrolled students.
     */
    public static function handleAssignStudents(array $data, ?ClassArm $presetArm = null): int
    {
        abort_unless(auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false, 403);

        $sessionId = (int) ($data['session_id'] ?? 0);
        $classroomId = (int) ($presetArm?->classroom_id ?? $data['classroom_id'] ?? 0);
        $armId = (int) ($presetArm?->id ?? $data['class_arm_id'] ?? 0);
        $studentIds = array_values(array_unique(array_map('intval', $data['student_ids'] ?? [])));

        $arm = ClassArm::query()
            ->with('classroom')
            ->where('id', $armId)
            ->where('classroom_id', $classroomId)
            ->firstOrFail();

        if (empty($studentIds)) {
            Notification::make()
                ->title('No Students Selected')
                ->body('Please select at least one student to assign to this class arm.')
                ->warning()
                ->send();

            return 0;
        }

        $updatedCount = DB::transaction(fn (): int => StudentEnrollment::query()
            ->where('session_id', $sessionId)
            ->where('classroom_id', $classroomId)
            ->whereIn('student_id', $studentIds)
            ->whereHas('student')
            ->update(['class_arm_id' => $arm->id]));

        Notification::make()
            ->title('Students Assigned to Class Arm')
            ->body("Assigned {$updatedCount} student(s) to {$arm->full_name}.")
            ->success()
            ->send();

        return $updatedCount;
    }

    protected static function getEligibleArmEnrollmentsQuery(
        int $sessionId,
        int $classroomId,
        int $targetArmId,
        bool $onlyUnassigned,
    ): Builder {
        return StudentEnrollment::query()
            ->where('session_id', $sessionId)
            ->where('classroom_id', $classroomId)
            ->whereHas('student')
            ->when(
                $onlyUnassigned,
                fn (Builder $query) => $query->whereNull('class_arm_id'),
                fn (Builder $query) => $query->where(function (Builder $sub) use ($targetArmId): void {
                    $sub->whereNull('class_arm_id')
                        ->orWhere('class_arm_id', '!=', $targetArmId);
                })
            );
    }

    protected static function getEligibleArmEnrollments(
        int $sessionId,
        int $classroomId,
        int $targetArmId,
        bool $onlyUnassigned,
    ): Collection {
        return static::getEligibleArmEnrollmentsQuery($sessionId, $classroomId, $targetArmId, $onlyUnassigned)
            ->with(['student.user', 'classArm'])
            ->get()
            ->sortBy(fn (StudentEnrollment $enrollment): string => strtolower($enrollment->student?->full_name ?? ''))
            ->values();
    }
}
