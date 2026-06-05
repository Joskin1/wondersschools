<?php

namespace App\Filament\Teacher\Pages;

use App\Jobs\CalculateTermResults;
use App\Models\ClassScoreStructure;
use App\Models\ClassTeacherAssignment;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\ResultCalculationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnterScores extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string | \UnitEnum | null $navigationGroup = 'Results';

    protected static ?string $title = 'Enter Scores';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.teacher.pages.enter-scores';

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

    public bool  $loaded          = false;
    public bool  $structureExists = false;

    // ── Lifecycle ─────────────────────────────────────────────────────────────

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
    }

    // ── Livewire updater hooks ────────────────────────────────────────────────

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

    // ── Core actions ──────────────────────────────────────────────────────────

    public function loadScores(): void
    {
        $this->clearScoreState();

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id || ! $this->subject_id) {
            return;
        }

        $user = Auth::user();

        // ── Server-side authorization ────────────────────────────────────────
        if (! $this->canEnterScoresFor($user, $this->subject_id, $this->classroom_id, $this->session_id, $this->term_id)) {
            Notification::make()
                ->title('You are not authorised to enter scores for this subject/class.')
                ->danger()
                ->send();
            $this->subject_id = null;
            return;
        }

        // ── Load class score structure ────────────────────────────────────────
        $structure = ClassScoreStructure::with(['items.scoreHead'])
            ->where('class_id',   $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id',    $this->term_id)
            ->first();

        if (! $structure || $structure->items->isEmpty()) {
            $this->structureExists = false;
            $this->loaded          = true;
            return;
        }

        $this->structureExists = true;

        $this->scoreHeads = $structure->items
            ->map(fn ($item) => [
                'id'            => $item->score_head_id,
                'name'          => $item->scoreHead->name,
                'effective_max' => $item->max_score_override ?? $item->scoreHead->max_score,
            ])
            ->toArray();

        // ── Load enrolled students with dynamic properties ────────────────────
        $enrolledIds = StudentEnrollment::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->pluck('student_id');

        $this->students = Student::whereIn('id', $enrolledIds)
            ->active()
            ->orderBy('full_name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id, 
                'full_name' => $s->full_name,
                'admission_number' => $s->admission_number ?? '—',
            ])
            ->toArray();

        // ── Load existing scores into map ─────────────────────────────────────
        $scoresMap = [];
        foreach ($this->students as $student) {
            foreach ($this->scoreHeads as $sh) {
                $scoresMap[$student['id']][$sh['id']] = '';
            }
        }

        $existingScores = Score::where('classroom_id', $this->classroom_id)
            ->where('subject_id',   $this->subject_id)
            ->where('session_id',   $this->session_id)
            ->where('term_id',      $this->term_id)
            ->whereIn('student_id', $enrolledIds)
            ->get();

        foreach ($existingScores as $score) {
            if (isset($scoresMap[$score->student_id][$score->score_head_id])) {
                $scoresMap[$score->student_id][$score->score_head_id] = $score->score !== null ? (string)(float)$score->score : '';
            }
        }

        $this->scores = $scoresMap;
        $this->loaded = true;
    }

    /**
     * Context-aware spreadsheet real-time autosave
     */
    public function saveScore(int $studentId, int $scoreHeadId, $value): array
    {
        $user = Auth::user();

        // ── Server-side guards ───────────────────────────────────────────────
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id || ! $this->subject_id) {
            return ['status' => 'error', 'message' => 'Missing session parameters.'];
        }

        $isFinalized = TermResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('is_finalized', true)
            ->exists();

        if ($isFinalized) {
            return ['status' => 'error', 'message' => 'Term results are finalized.'];
        }

        if (! $this->canEnterScoresFor($user, $this->subject_id, $this->classroom_id, $this->session_id, $this->term_id)) {
            return ['status' => 'error', 'message' => 'Unauthorized action.'];
        }

        $sh = collect($this->scoreHeads)->firstWhere('id', $scoreHeadId);
        if (! $sh) {
            return ['status' => 'error', 'message' => 'Invalid score head.'];
        }

        if ($value !== '' && $value !== null) {
            $numericValue = (float) $value;
            if ($numericValue < 0 || $numericValue > $sh['effective_max']) {
                return [
                    'status' => 'error', 
                    'message' => "exceeds maximum of {$sh['effective_max']}."
                ];
            }
        } else {
            $numericValue = null;
        }

        // ── Update local scores array state ──────────────────────────────────
        $this->scores[$studentId][$scoreHeadId] = $value !== '' ? (string)(float)$value : '';

        // ── Persist to Database ──────────────────────────────────────────────
        DB::transaction(function () use ($studentId, $scoreHeadId, $numericValue, $user) {
            if ($numericValue === null) {
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
                        'score'        => $numericValue,
                    ]
                );
            }
        });

        // ── Invalidate computed result caches ────────────────────────────────
        SubjectResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->delete();

        TermResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->delete();

        $structure = ClassScoreStructure::where('class_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->first();

        if ($structure?->locked) {
            CalculateTermResults::dispatch($this->classroom_id, $this->session_id, $this->term_id);
        }

        // Recalculate values for this student
        $studentTotal = collect($this->scoreHeads)->sum(
            fn($h) => (float) ($this->scores[$studentId][$h['id']] ?? 0)
        );
        $hasAny = collect($this->scoreHeads)->contains(
            fn($h) => ($this->scores[$studentId][$h['id']] ?? '') !== ''
        );

        $gradeInfo = app(\App\Services\ResultCalculationService::class)->resolveGrade($studentTotal);

        return [
            'status'        => 'success',
            'student_total' => $hasAny ? number_format($studentTotal, 1) : '—',
            'student_grade' => $hasAny ? $gradeInfo['grade'] : '—',
            'stats'         => $this->stats,
        ];
    }

    /**
     * Alias for Livewire tests expecting `saveScores`.
     */
    public function saveScores(int $studentId, int $scoreHeadId, $value): array
    {
        return $this->saveScore($studentId, $scoreHeadId, $value);
    }

    // ── Computed properties ───────────────────────────────────────────────────

    public function getStatsProperty(): array
    {
        if (! $this->loaded || empty($this->students) || empty($this->scoreHeads)) {
            return [];
        }

        $maxTotal = collect($this->scoreHeads)->sum(fn($sh) => $sh['effective_max']);
        $totalCells = count($this->students) * count($this->scoreHeads);
        $filledCells = 0;
        $totals = [];

        foreach ($this->students as $student) {
            $studentTotal = 0;
            $hasAny = false;
            foreach ($this->scoreHeads as $sh) {
                $val = $this->scores[$student['id']][$sh['id']] ?? '';
                if ($val !== '') {
                    $filledCells++;
                    $studentTotal += (float) $val;
                    $hasAny = true;
                }
            }
            if ($hasAny) {
                $totals[] = $studentTotal;
            }
        }

        $completionRate = $totalCells > 0 ? round(($filledCells / $totalCells) * 100) : 0;
        $average = count($totals) > 0 ? round(array_sum($totals) / count($totals), 1) : 0;
        $highest = count($totals) > 0 ? max($totals) : 0;
        $lowest = count($totals) > 0 ? min($totals) : 0;

        $passes = collect($totals)->filter(fn($t) => $t >= 40)->count();
        $fails = count($totals) - $passes;
        $passPercent = count($totals) > 0 ? round(($passes / count($totals)) * 100) : 0;

        return [
            'completion_rate' => $completionRate,
            'average'         => $average,
            'highest'         => $highest,
            'lowest'          => $lowest,
            'max_total'       => $maxTotal,
            'pass_count'      => $passes,
            'fail_count'      => $fails,
            'pass_percent'    => $passPercent,
        ];
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

    public function getAuthorizedClassroomsProperty()
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

    public function getAuthorizedSubjectsProperty()
    {
        $user = Auth::user();
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return collect();
        }

        if ($user->canManageAcademics() || ClassTeacherAssignment::isClassTeacher($user->id, $this->classroom_id, $this->session_id)) {
            $classroom = Classroom::find($this->classroom_id);
            if ($classroom) {
                $subjects = $classroom->subjects()->active()->orderBy('name')->get();
                if ($subjects->isNotEmpty()) {
                    return $subjects;
                }
            }

            $subjectIds = TeacherSubjectAssignment::where('classroom_id', $this->classroom_id)
                ->where('session_id', $this->session_id)
                ->where('term_id',    $this->term_id)
                ->pluck('subject_id')
                ->unique();

            return Subject::whereIn('id', $subjectIds)->active()->orderBy('name')->get();
        }

        $subjectIds = TeacherSubjectAssignment::where('teacher_id',  $user->id)
            ->where('classroom_id', $this->classroom_id)
            ->where('session_id',   $this->session_id)
            ->where('term_id',      $this->term_id)
            ->pluck('subject_id');

        return Subject::whereIn('id', $subjectIds)->active()->orderBy('name')->get();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user?->isTeacher() || $user?->canManageAcademics() ?? false;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function canEnterScoresFor(
        $user,
        int $subjectId,
        int $classroomId,
        int $sessionId,
        int $termId
    ): bool {
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
        $this->loaded         = false;
        $this->structureExists = false;
    }
}
