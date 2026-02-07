<?php

namespace App\Filament\Teacher\Pages;

use App\Models\Classroom;
use App\Models\Score;
use App\Models\ScoreHeader;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ScoreService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ScoreEntryPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-pencil-square';
    
    protected string $view = 'filament.teacher.pages.score-entry-page';
    
    protected static ?string $navigationLabel = 'Score Entry';
    
    protected static ?string $title = 'Bulk Score Entry';
    
    protected static string|\UnitEnum|null $navigationGroup = 'Score Management';
    
    protected static ?int $navigationSort = 2;

    // Filter properties
    public ?int $classroom_id = null;
    public ?int $subject_id = null;
    public ?int $score_header_id = null;
    public ?string $session = null;
    public ?int $term = null;

    // Store scores data
    public array $scores = [];

    public function mount(): void
    {
        // Set default session
        $this->session = now()->year . '/' . (now()->year + 1);
        $this->term = 1;
    }

    protected function getForms(): array
    {
        return [
            'filterForm',
        ];
    }

    public function filterForm(Form $form): Form
    {
        $user = auth()->user();
        $scoreService = app(ScoreService::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Select Criteria')
                    ->description('Choose the class, subject, and score type to enter scores for')
                    ->schema([
                        Forms\Components\Select::make('session')
                            ->label('Academic Session')
                            ->options(function () {
                                $currentYear = now()->year;
                                return [
                                    ($currentYear - 1) . '/' . $currentYear => ($currentYear - 1) . '/' . $currentYear,
                                    $currentYear . '/' . ($currentYear + 1) => $currentYear . '/' . ($currentYear + 1),
                                    ($currentYear + 1) . '/' . ($currentYear + 2) => ($currentYear + 1) . '/' . ($currentYear + 2),
                                ];
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn() => $this->resetScores()),

                        Forms\Components\Select::make('term')
                            ->label('Term')
                            ->options([
                                1 => 'First Term',
                                2 => 'Second Term',
                                3 => 'Third Term',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn() => $this->resetScores()),

                        Forms\Components\Select::make('classroom_id')
                            ->label('Classroom')
                            ->options(function () use ($user, $scoreService) {
                                if ($user->isAdmin()) {
                                    return Classroom::pluck('name', 'id')->toArray();
                                }
                                
                                if ($user->isTeacher() && $this->session) {
                                    return $scoreService->getTeacherClassrooms($user)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }
                                
                                return [];
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn() => $this->resetScores()),

                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->options(function () use ($user, $scoreService) {
                                if (!$this->classroom_id || !$this->session) {
                                    return [];
                                }

                                if ($user->isAdmin()) {
                                    return Subject::whereHas('classrooms', function ($query) {
                                        $query->where('classrooms.id', $this->classroom_id);
                                    })->pluck('name', 'id')->toArray();
                                }
                                
                                if ($user->isTeacher()) {
                                    // Get subjects teacher is assigned to for this classroom
                                    return DB::table('classroom_subject_teacher')
                                        ->join('subjects', 'classroom_subject_teacher.subject_id', '=', 'subjects.id')
                                        ->where('classroom_subject_teacher.staff_id', $user->staff->id)
                                        ->where('classroom_subject_teacher.classroom_id', $this->classroom_id)
                                        ->where('classroom_subject_teacher.session', $this->session)
                                        ->pluck('subjects.name', 'subjects.id')
                                        ->toArray();
                                }
                                
                                return [];
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn() => $this->resetScores()),

                        Forms\Components\Select::make('score_header_id')
                            ->label('Score Type')
                            ->options(function () {
                                if (!$this->classroom_id || !$this->session || !$this->term) {
                                    return [];
                                }

                                return ScoreHeader::where('classroom_id', $this->classroom_id)
                                    ->where('session', $this->session)
                                    ->where('term', $this->term)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn() => $this->loadScores())
                            ->helperText('Select CA1, CA2, Exam, etc.'),
                    ])
                    ->columns(3),
            ])
            ->statePath('filterForm');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Student Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('admission_number')
                    ->label('Admission No.')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextInputColumn::make('score')
                    ->label('Score')
                    ->rules(['nullable', 'numeric', 'min:0', 'max:100'])
                    ->state(function (Student $record): ?float {
                        return $this->scores[$record->id] ?? $this->getExistingScore($record->id);
                    })
                    ->updateStateUsing(function (Student $record, $state) {
                        $this->scores[$record->id] = $state;
                    }),
            ])
            ->paginated(false)
            ->heading($this->getTableHeading());
    }

    protected function getTableQuery(): Builder
    {
        if (!$this->classroom_id) {
            return Student::query()->whereRaw('1 = 0'); // Empty query
        }

        return Student::whereHas('classrooms', function ($query) {
            $query->where('classrooms.id', $this->classroom_id);
        })->orderBy('name');
    }

    protected function getTableHeading(): ?string
    {
        if (!$this->classroom_id || !$this->subject_id || !$this->score_header_id) {
            return 'Please select all criteria above to load students';
        }

        $classroom = Classroom::find($this->classroom_id);
        $subject = Subject::find($this->subject_id);
        $scoreHeader = ScoreHeader::find($this->score_header_id);

        return "Enter scores for {$subject?->name} - {$scoreHeader?->name} ({$classroom?->name})";
    }

    protected function getExistingScore(int $studentId): ?float
    {
        if (!$this->subject_id || !$this->score_header_id || !$this->session || !$this->term) {
            return null;
        }

        $score = Score::where('student_id', $studentId)
            ->where('subject_id', $this->subject_id)
            ->where('classroom_id', $this->classroom_id)
            ->where('score_header_id', $this->score_header_id)
            ->where('session', $this->session)
            ->where('term', $this->term)
            ->first();

        return $score?->value;
    }

    protected function loadScores(): void
    {
        if (!$this->classroom_id || !$this->subject_id || !$this->score_header_id) {
            return;
        }

        // Load existing scores
        $existingScores = Score::where('subject_id', $this->subject_id)
            ->where('classroom_id', $this->classroom_id)
            ->where('score_header_id', $this->score_header_id)
            ->where('session', $this->session)
            ->where('term', $this->term)
            ->get()
            ->pluck('value', 'student_id')
            ->toArray();

        $this->scores = $existingScores;
    }

    protected function resetScores(): void
    {
        $this->scores = [];
    }

    public function save(): void
    {
        // Validate filters are set
        if (!$this->classroom_id || !$this->subject_id || !$this->score_header_id || !$this->session || !$this->term) {
            Notification::make()
                ->title('Error')
                ->body('Please select all criteria before saving scores.')
                ->danger()
                ->send();
            return;
        }

        // Validate teacher assignment
        $user = auth()->user();
        if ($user->isTeacher()) {
            $scoreService = app(ScoreService::class);
            if (!$scoreService->validateTeacherAssignment($user, $this->subject_id, $this->classroom_id, $this->session)) {
                Notification::make()
                    ->title('Unauthorized')
                    ->body('You are not authorized to enter scores for this subject-classroom combination.')
                    ->danger()
                    ->send();
                return;
            }
        }

        DB::beginTransaction();

        try {
            $saved = 0;
            $skipped = 0;

            foreach ($this->scores as $studentId => $value) {
                // Skip if no value entered
                if ($value === null || $value === '') {
                    $skipped++;
                    continue;
                }

                // Validate score range
                if ($value < 0 || $value > 100) {
                    continue;
                }

                Score::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $this->subject_id,
                        'classroom_id' => $this->classroom_id,
                        'score_header_id' => $this->score_header_id,
                        'session' => $this->session,
                        'term' => $this->term,
                    ],
                    [
                        'value' => $value,
                    ]
                );

                $saved++;
            }

            DB::commit();

            Notification::make()
                ->title('Success')
                ->body("Saved {$saved} scores successfully. {$skipped} students skipped (no score entered).")
                ->success()
                ->send();

            // Reload scores to show updated values
            $this->loadScores();

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error')
                ->body('Failed to save scores: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save All Scores')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save')
                ->requiresConfirmation()
                ->modalHeading('Save Scores')
                ->modalDescription('Are you sure you want to save all entered scores?')
                ->modalSubmitActionLabel('Yes, Save'),
        ];
    }
}
