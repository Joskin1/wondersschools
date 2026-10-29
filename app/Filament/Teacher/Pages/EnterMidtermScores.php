<?php

namespace App\Filament\Teacher\Pages;

use App\Models\ClassMidtermConfig;
use App\Models\ClassScoreStructure;
use App\Models\ClassTeacherAssignment;
use App\Models\Classroom;
use App\Models\MidtermClassTermApproval;
use App\Models\MidtermSubjectResult;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermScoringSnapshot;
use App\Services\MidtermCalculationService;
use App\Services\ResultCalculationService;
use App\Services\ScoreSpreadsheetService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;

class EnterMidtermScores extends Page
{
    use WithFileUploads;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Results';

    protected static ?string $title = 'Enter Mid-Term Scores';

    protected static ?string $navigationLabel = 'Mid-Term Scores';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.teacher.pages.enter-midterm-scores';

    // ── Filter state ──────────────────────────────────────────────────────────

    public ?int $session_id   = null;
    public ?int $term_id      = null;
    public ?int $classroom_id = null;
    public ?int $subject_id   = null;

    // ── Loaded state ──────────────────────────────────────────────────────────

    /** @var array<int, array{id: int, full_name: string, admission_number: string}> */
    public array $students   = [];

    /** @var array<int, array{id: int, name: string, effective_max: int}> */
    public array $scoreHeads = [];

    /** @var array<int, array<int, string>> scores[studentId][scoreHeadId] = raw value */
    public array $scores     = [];

    /** @var array<int, array<int, bool>> absents[studentId][scoreHeadId] = is_absent */
    public array $absents    = [];

    /** @var array<int, array{status: string, raw_score: float, scaled_score: float|null, grade: string, remark: string}> */
    public array $studentResults = [];

    public int   $midtermWeight  = 40;
    public int   $rawMax         = 0;
    public bool  $loaded         = false;
    public bool  $structureExists = false;
    public bool  $isLocked       = false;
    public ?string $configError  = null;

    // ── Import state ──────────────────────────────────────────────────────────

    public $uploadFile = null;
    public bool $showImportModal = false;
    public array $stagingData = [];

    public static function shouldRegisterNavigation(): bool
    {
        return filter_var(Setting::where('key', 'use_midterm_results')->value('value'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function canAccess(): bool
    {
        return filter_var(Setting::where('key', 'use_midterm_results')->value('value'), FILTER_VALIDATE_BOOLEAN);
    }

    public function mount(): void
    {
        $activeSession = Session::active()->first();
        if ($activeSession) {
            $this->session_id = $activeSession->id;
            $activeTerm = $activeSession->activeTerm;
            if ($activeTerm) {
                $this->term_id = $activeTerm->id;
            }
        }

        if (request()->has('session_id')) {
            $this->session_id = (int) request()->query('session_id');
        }
        if (request()->has('term_id')) {
            $this->term_id = (int) request()->query('term_id');
        }
        if (request()->has('classroom_id')) {
            $this->classroom_id = (int) request()->query('classroom_id');
        }
        if (request()->has('subject_id')) {
            $this->subject_id = (int) request()->query('subject_id');
        }

        if ($this->session_id && $this->term_id && $this->classroom_id && $this->subject_id) {
            $this->loadScores();
        }
    }

    public function updatedSessionId(): void
    {
        $this->term_id      = null;
        $this->classroom_id = null;
        $this->subject_id   = null;
        $this->clearScoreState();
    }

    public function updatedTermId(): void
    {
        $this->classroom_id = null;
        $this->subject_id   = null;
        $this->clearScoreState();
    }

    public function updatedClassroomId(): void
    {
        $this->subject_id = null;
        $this->clearScoreState();
    }

    public function updatedSubjectId(): void
    {
        $this->loadScores();
    }

    public function loadScores(): void
    {
        $this->clearScoreState();

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id || ! $this->subject_id) {
            return;
        }

        $user = Auth::user();
        if (! $this->canEnterScoresFor($user, $this->subject_id, $this->classroom_id, $this->session_id, $this->term_id)) {
            Notification::make()->title('You are not authorised to enter scores for this subject/class.')->danger()->send();
            $this->subject_id = null;
            return;
        }

        // Check if locked
        $this->isLocked = MidtermClassTermApproval::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('is_locked', true)
            ->exists();

        // Check snapshot first
        $snapshot = TermScoringSnapshot::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->first();

        if ($snapshot) {
            if ($snapshot->mode !== 'midterm') {
                $this->configError = 'This class is operating in Legacy mode for this term (no mid-term assessment).';
                $this->structureExists = false;
                $this->loaded = true;
                return;
            }

            $this->midtermWeight = (int) $snapshot->midterm_weight;
            $this->rawMax        = (int) $snapshot->raw_max;
            $this->scoreHeads    = $snapshot->midterm_heads;
            $this->structureExists = true;
        } else {
            // No snapshot yet: verify configurations exist
            $midtermConfig = ClassMidtermConfig::with(['heads.scoreHead'])
                ->where('classroom_id', $this->classroom_id)
                ->first();

            if (! $midtermConfig || $midtermConfig->heads->isEmpty()) {
                $this->configError = 'Mid-term score heads have not been configured for this class. Please configure them in Results → Mid-Term Structure.';
                $this->structureExists = false;
                $this->loaded = true;
                return;
            }

            // Guard: Exam structure MUST also exist before scoring starts!
            $examStructure = ClassScoreStructure::with(['items.scoreHead'])
                ->where('class_id', $this->classroom_id)
                ->where('session_id', $this->session_id)
                ->where('term_id', $this->term_id)
                ->first();

            if (! $examStructure || $examStructure->items->isEmpty()) {
                $this->configError = 'Exam score structure has not been configured for this class and term. Admins must set up the exam structure in Results → Score Structure before scores can be entered.';
                $this->structureExists = false;
                $this->loaded = true;
                return;
            }

            $this->midtermWeight = (int) $midtermConfig->midterm_weight;
            $this->scoreHeads = $midtermConfig->heads
                ->filter(fn ($h) => $h->scoreHead !== null)
                ->map(fn ($h) => [
                    'id'            => $h->score_head_id,
                    'name'          => $h->scoreHead->name,
                    'effective_max' => (int) ($h->max_score_override ?? $h->scoreHead->max_score),
                ])
                ->values()
                ->toArray();

            $this->rawMax = (int) collect($this->scoreHeads)->sum('effective_max');
            $this->structureExists = true;
        }

        // Load enrolled students
        $enrolledIds = StudentEnrollment::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->pluck('student_id');

        $this->students = Student::whereIn('id', $enrolledIds)
            ->active()
            ->with('user')
            ->get()
            ->sortBy(fn ($s) => $s->full_name)
            ->values()
            ->map(fn ($s) => [
                'id'               => $s->id,
                'full_name'        => $s->full_name,
                'admission_number' => $s->admission_number ?? '—',
            ])
            ->toArray();

        // Load existing scores
        $headIds = collect($this->scoreHeads)->pluck('id')->toArray();
        $existingScores = Score::where('classroom_id', $this->classroom_id)
            ->where('subject_id', $this->subject_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->whereIn('student_id', $enrolledIds)
            ->whereIn('score_head_id', $headIds)
            ->get();

        $scoresMap = [];
        $absentsMap = [];
        foreach ($this->students as $student) {
            foreach ($this->scoreHeads as $sh) {
                $scoresMap[$student['id']][$sh['id']] = '';
                $absentsMap[$student['id']][$sh['id']] = false;
            }
        }

        foreach ($existingScores as $score) {
            if (isset($scoresMap[$score->student_id][$score->score_head_id])) {
                $absentsMap[$score->student_id][$score->score_head_id] = (bool) $score->is_absent;
                $scoresMap[$score->student_id][$score->score_head_id] = $score->is_absent
                    ? ''
                    : ($score->score !== null ? (string)(float)$score->score : '');
            }
        }

        $this->scores = $scoresMap;
        $this->absents = $absentsMap;

        // Load existing midterm subject results
        $midtermResults = MidtermSubjectResult::where('classroom_id', $this->classroom_id)
            ->where('subject_id', $this->subject_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->whereIn('student_id', $enrolledIds)
            ->get()
            ->keyBy('student_id');

        $resultsMap = [];
        foreach ($this->students as $st) {
            $sId = $st['id'];
            $r = $midtermResults->get($sId);
            $resultsMap[$sId] = [
                'status'       => $r?->status ?? 'unscored',
                'raw_score'    => (float) ($r?->raw_score ?? 0),
                'scaled_score' => $r?->scaled_score !== null ? (float) $r->scaled_score : null,
                'grade'        => $r?->grade ?? '—',
                'remark'       => $r?->remark ?? '—',
            ];
        }

        $this->studentResults = $resultsMap;
        $this->loaded = true;
    }

    public function saveScore(int $studentId, int $scoreHeadId, $value, bool $isAbsent = false): array
    {
        $user = Auth::user();

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id || ! $this->subject_id) {
            return ['status' => 'error', 'message' => 'Missing session parameters.'];
        }

        // Check lock
        $isLocked = MidtermClassTermApproval::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('is_locked', true)
            ->exists();

        if ($isLocked) {
            return ['status' => 'error', 'message' => 'Mid-term results are locked for this class.'];
        }

        if (! $this->canEnterScoresFor($user, $this->subject_id, $this->classroom_id, $this->session_id, $this->term_id)) {
            return ['status' => 'error', 'message' => 'Unauthorized action.'];
        }

        $sh = collect($this->scoreHeads)->firstWhere('id', $scoreHeadId);
        if (! $sh) {
            return ['status' => 'error', 'message' => 'Invalid score head.'];
        }

        $numericValue = null;
        if (! $isAbsent && $value !== '' && $value !== null) {
            $numericValue = (float) $value;
            if ($numericValue < 0) {
                return ['status' => 'error', 'message' => 'Score cannot be negative.'];
            }
            if ($numericValue > $sh['effective_max']) {
                return ['status' => 'error', 'message' => "Score exceeds maximum of {$sh['effective_max']}."];
            }
        }

        // First-save auto-snapshot
        try {
            $snapshot = app(MidtermCalculationService::class)->getOrCreateSnapshot(
                $this->classroom_id,
                $this->session_id,
                $this->term_id,
                $user->id
            );
        } catch (\DomainException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Update local state
        $this->absents[$studentId][$scoreHeadId] = $isAbsent;
        $this->scores[$studentId][$scoreHeadId] = $isAbsent ? '' : ($value !== '' && $value !== null ? (string)(float)$value : '');

        // Persist to database
        DB::transaction(function () use ($studentId, $scoreHeadId, $numericValue, $isAbsent, $user) {
            if ($numericValue === null && ! $isAbsent) {
                Score::where([
                    'student_id'    => $studentId,
                    'subject_id'    => $this->subject_id,
                    'score_head_id' => $scoreHeadId,
                    'session_id'    => $this->session_id,
                    'term_id'       => $this->term_id,
                ])->delete();
            } else {
                Score::updateOrCreate(
                    [
                        'student_id'    => $studentId,
                        'subject_id'    => $this->subject_id,
                        'score_head_id' => $scoreHeadId,
                        'session_id'    => $this->session_id,
                        'term_id'       => $this->term_id,
                    ],
                    [
                        'classroom_id' => $this->classroom_id,
                        'teacher_id'   => $user->id,
                        'score'        => $isAbsent ? 0 : $numericValue,
                        'is_absent'    => $isAbsent,
                    ]
                );
            }
        });

        // Compute student midterm result for this subject
        $midtermCalc = app(MidtermCalculationService::class);
        $policy = Setting::where('key', 'midterm_absent_policy')->value('value') ?? 'count_as_zero';
        $decimalPlaces = (int) (Setting::where('key', 'result_decimal_places')->value('value') ?? 2);
        $roundingRule = Setting::where('key', 'result_rounding_rule')->value('value') ?? 'half_up';

        $res = $midtermCalc->computeStudentSubjectMidterm(
            $studentId,
            $this->subject_id,
            $this->classroom_id,
            $this->session_id,
            $this->term_id,
            $snapshot,
            $policy,
            $decimalPlaces,
            $roundingRule
        );

        if ($res === null) {
            unset($this->studentResults[$studentId]);
            return [
                'status'        => 'success',
                'raw_total'     => 0.0,
                'scaled_score'  => '—',
                'student_grade' => '—',
                'midterm_status'=> null,
            ];
        }

        $this->studentResults[$studentId] = [
            'status'       => $res->status,
            'raw_score'    => (float) $res->raw_score,
            'scaled_score' => $res->scaled_score !== null ? (float) $res->scaled_score : null,
            'grade'        => $res->grade,
            'remark'       => $res->remark,
        ];

        // If terminal results exist for this student/subject, trigger recalculation immediately
        $hasTerminal = SubjectResult::where('classroom_id', $this->classroom_id)
            ->where('subject_id', $this->subject_id)
            ->where('student_id', $studentId)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->exists();

        if ($hasTerminal) {
            app(ResultCalculationService::class)->calculateForSubject(
                $this->classroom_id,
                $this->subject_id,
                $this->session_id,
                $this->term_id
            );
        }

        $displayScaled = match ($res->status) {
            'absent'  => 'ABS',
            'missing' => '—',
            default   => number_format((float) $res->scaled_score, $decimalPlaces),
        };

        return [
            'status'        => 'success',
            'raw_total'     => (float) $res->raw_score,
            'scaled_score'  => $displayScaled,
            'student_grade' => $res->grade,
            'midterm_status'=> $res->status,
        ];
    }

    public function exportCurrentSubject()
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id || ! $this->subject_id) {
            Notification::make()->title('Please select all filters first.')->warning()->send();
            return null;
        }

        $path = app(ScoreSpreadsheetService::class)->exportMidtermToTempFile(
            $this->classroom_id,
            [$this->subject_id],
            $this->session_id,
            $this->term_id,
            Auth::id()
        );

        $subject = Subject::find($this->subject_id);
        $classroom = Classroom::find($this->classroom_id);
        $filename = "Midterm_{$classroom->name}_{$subject->name}.xlsx";

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function exportClassSubjects()
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and class first.')->warning()->send();
            return null;
        }

        $user = Auth::user();
        $assignedSubjectIds = $this->getAuthorizedSubjectIds($user);

        if (empty($assignedSubjectIds)) {
            Notification::make()->title('No subjects assigned to you in this class.')->warning()->send();
            return null;
        }

        $path = app(ScoreSpreadsheetService::class)->exportMidtermToTempFile(
            $this->classroom_id,
            $assignedSubjectIds,
            $this->session_id,
            $this->term_id,
            $user->id
        );

        $classroom = Classroom::find($this->classroom_id);
        $filename = "Midterm_AllSubjects_{$classroom->name}.xlsx";

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function updatedUploadFile(): void
    {
        $this->validate([
            'uploadFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $user = Auth::user();
            $assignedSubjectIds = $this->getAuthorizedSubjectIds($user);

            $filePath = $this->uploadFile->getRealPath();
            $this->stagingData = app(ScoreSpreadsheetService::class)->parseAndValidate(
                $filePath,
                $this->classroom_id,
                $assignedSubjectIds,
                $this->session_id,
                $this->term_id,
                'midterm'
            );

            $this->showImportModal = true;
        } catch (\Throwable $e) {
            Notification::make()->title('Failed to parse file')->body($e->getMessage())->danger()->send();
            $this->uploadFile = null;
            $this->showImportModal = false;
        }
    }

    public function updateStagingScore(int $studentIndex, string $scoreKey, $value): void
    {
        if (! isset($this->stagingData['students'][$studentIndex]['scores'][$scoreKey])) {
            return;
        }

        $cell = &$this->stagingData['students'][$studentIndex]['scores'][$scoreKey];
        $effectiveMax = (float) $cell['effective_max'];
        $cleanVal = trim((string) $value);

        if ($cleanVal === '') {
            $cell['value'] = '';
            $cell['is_absent'] = false;
            $cell['error'] = null;
        } elseif (strtoupper($cleanVal) === 'ABS') {
            $cell['value'] = 'ABS';
            $cell['is_absent'] = true;
            $cell['error'] = null;
        } elseif (! is_numeric($cleanVal)) {
            $cell['value'] = $cleanVal;
            $cell['is_absent'] = false;
            $cell['error'] = 'Must be a number or ABS.';
        } else {
            $num = (float) $cleanVal;
            if ($num < 0) {
                $cell['value'] = (string) $num;
                $cell['is_absent'] = false;
                $cell['error'] = 'Cannot be negative.';
            } elseif ($num > $effectiveMax) {
                $cell['value'] = (string) $num;
                $cell['is_absent'] = false;
                $cell['error'] = "Exceeds max of {$effectiveMax}.";
            } else {
                $cell['value'] = (string) $num;
                $cell['is_absent'] = false;
                $cell['error'] = null;
            }
        }

        // Recalculate row error status
        $rowHasError = false;
        if (! empty($this->stagingData['students'][$studentIndex]['unmatched'])) {
            $rowHasError = true;
        }
        foreach ($this->stagingData['students'][$studentIndex]['scores'] as $s) {
            if (! empty($s['error'])) {
                $rowHasError = true;
                break;
            }
        }
        $this->stagingData['students'][$studentIndex]['has_error'] = $rowHasError;

        // Recalculate total errors & valid scores
        $totalErrors = 0;
        $totalScores = 0;
        foreach ($this->stagingData['students'] as $st) {
            if (! empty($st['unmatched'])) {
                $totalErrors++;
            }
            foreach ($st['scores'] as $sc) {
                if (! empty($sc['error'])) {
                    $totalErrors++;
                }
                if ($sc['value'] !== '' && empty($sc['error'])) {
                    $totalScores++;
                }
            }
        }

        $this->stagingData['total_errors'] = $totalErrors;
        $this->stagingData['total_scores_count'] = $totalScores;
    }

    public function confirmImport(): void
    {
        $this->commitImport();
    }

    public function commitImport(): void
    {
        if (empty($this->stagingData) || empty($this->stagingData['students'])) {
            Notification::make()->title('No staging data to commit.')->warning()->send();
            return;
        }

        if (($this->stagingData['total_errors'] ?? 0) > 0) {
            Notification::make()->title('Please resolve all errors before importing.')->danger()->send();
            return;
        }

        if ($this->isLocked) {
            Notification::make()->title('Mid-term results are locked.')->danger()->send();
            return;
        }

        $user = Auth::user();
        $midtermCalc = app(MidtermCalculationService::class);

        // Ensure snapshot
        try {
            $snapshot = $midtermCalc->getOrCreateSnapshot($this->classroom_id, $this->session_id, $this->term_id, $user->id);
        } catch (\DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            return;
        }

        $now = now();
        $records = [];
        foreach ($this->stagingData['students'] as $student) {
            $studentId = $student['student_id'];
            if (! $studentId) {
                continue;
            }

            foreach ($student['scores'] as $score) {
                $val = $score['value'];
                $isAbsent = (bool) ($score['is_absent'] ?? false);
                if ($val === '' && ! $isAbsent) {
                    continue;
                }

                $records[] = [
                    'student_id'    => (int) $studentId,
                    'classroom_id'  => (int) $this->classroom_id,
                    'subject_id'    => (int) $score['subject_id'],
                    'score_head_id' => (int) $score['score_head_id'],
                    'session_id'    => (int) $this->session_id,
                    'term_id'       => (int) $this->term_id,
                    'teacher_id'    => (int) $user->id,
                    'score'         => $isAbsent ? 0.0 : (float) $val,
                    'is_absent'     => $isAbsent,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        $savedCount = count($records);
        if (! empty($records)) {
            DB::transaction(function () use ($records) {
                foreach (array_chunk($records, 200) as $chunk) {
                    Score::upsert(
                        $chunk,
                        ['student_id', 'subject_id', 'score_head_id', 'session_id', 'term_id'],
                        ['classroom_id', 'teacher_id', 'score', 'is_absent', 'updated_at']
                    );
                }
            });
        }

        // Recalculate midterm results for all affected subjects and students
        $policy = Setting::where('key', 'midterm_absent_policy')->value('value') ?? 'count_as_zero';
        $decimalPlaces = (int) (Setting::where('key', 'result_decimal_places')->value('value') ?? 2);
        $roundingRule = Setting::where('key', 'result_rounding_rule')->value('value') ?? 'half_up';

        $affectedSubjectIds = collect($records)->pluck('subject_id')->unique();
        if ($this->subject_id && ! $affectedSubjectIds->contains($this->subject_id)) {
            $affectedSubjectIds->push($this->subject_id);
        }

        $enrolledStudents = StudentEnrollment::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->pluck('student_id');

        foreach ($affectedSubjectIds as $subjId) {
            foreach ($enrolledStudents as $studId) {
                $midtermCalc->computeStudentSubjectMidterm(
                    $studId,
                    $subjId,
                    $this->classroom_id,
                    $this->session_id,
                    $this->term_id,
                    $snapshot,
                    $policy,
                    $decimalPlaces,
                    $roundingRule
                );
            }
        }

        $this->showImportModal = false;
        $this->stagingData = [];
        $this->uploadFile = null;
        $this->loadScores();

        Notification::make()->title("Imported {$savedCount} scores successfully.")->success()->send();
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->stagingData = [];
        $this->uploadFile = null;
    }

    public function getSessionsProperty()
    {
        return Session::orderBy('start_year', 'desc')->get();
    }

    public function getTermsProperty()
    {
        if (! $this->session_id) {
            return collect();
        }
        return Term::where('session_id', $this->session_id)->orderBy('order')->get();
    }

    public function getClassroomsProperty()
    {
        $user = Auth::user();
        if (! $this->session_id || ! $this->term_id) {
            return collect();
        }

        if ($user->canManageAcademics()) {
            return Classroom::active()->ordered()->get();
        }

        $classTeacherIds = ClassTeacherAssignment::where('teacher_id', $user->id)
            ->where('session_id', $this->session_id)
            ->pluck('class_id')
            ->toArray();

        $subjectTeacherIds = TeacherSubjectAssignment::where('teacher_id', $user->id)
            ->where('session_id', $this->session_id)
            ->where('term_id',    $this->term_id)
            ->pluck('classroom_id')
            ->toArray();

        $allIds = array_unique(array_merge($classTeacherIds, $subjectTeacherIds));

        return Classroom::whereIn('id', $allIds)->active()->ordered()->get();
    }

    public function getSubjectsProperty()
    {
        $user = Auth::user();
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return collect();
        }

        if ($user->canManageAcademics() || ClassTeacherAssignment::isClassTeacher($user->id, $this->classroom_id, $this->session_id)) {
            $classroom = Classroom::find($this->classroom_id);
            $classSubjectIds = $classroom ? $classroom->subjects()->active()->pluck('subjects.id')->toArray() : [];
            $assignedSubjectIds = TeacherSubjectAssignment::where('classroom_id', $this->classroom_id)
                ->where('session_id', $this->session_id)
                ->where('term_id',    $this->term_id)
                ->pluck('subject_id')
                ->unique()
                ->toArray();
            $allSubjectIds = array_values(array_unique(array_merge($classSubjectIds, $assignedSubjectIds)));

            return Subject::whereIn('id', $allSubjectIds)->active()->orderBy('name')->get();
        }

        $assignedSubjectIds = TeacherSubjectAssignment::where('teacher_id', $user->id)
            ->where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id',    $this->term_id)
            ->pluck('subject_id')
            ->unique();

        return Subject::whereIn('id', $assignedSubjectIds)->active()->orderBy('name')->get();
    }

    private function getAuthorizedSubjectIds($user): array
    {
        return $this->subjects->pluck('id')->toArray();
    }

    private function canEnterScoresFor($user, int $subjectId, int $classroomId, int $sessionId, int $termId): bool
    {
        if ($user->canManageAcademics()) {
            return true;
        }

        if (! $user->isTeacher()) {
            return false;
        }

        if (ClassTeacherAssignment::isClassTeacher($user->id, $classroomId, $sessionId)) {
            return true;
        }

        return TeacherSubjectAssignment::isAssigned($user->id, $subjectId, $classroomId, $sessionId, $termId);
    }

    private function clearScoreState(): void
    {
        $this->students       = [];
        $this->scoreHeads     = [];
        $this->scores         = [];
        $this->absents        = [];
        $this->studentResults = [];
        $this->loaded         = false;
        $this->structureExists = false;
        $this->isLocked       = false;
        $this->configError    = null;
    }
}
