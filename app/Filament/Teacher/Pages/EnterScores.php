<?php

namespace App\Filament\Teacher\Pages;

use App\Models\ClassScoreStructure;
use App\Models\ClassTeacherAssignment;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    /** @var array<int, array{id: int, full_name: string}> */
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

        // ── Load enrolled students ────────────────────────────────────────────
        $enrolledIds = StudentEnrollment::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->pluck('student_id');

        $this->students = Student::whereIn('id', $enrolledIds)
            ->active()
            ->orderBy('full_name')
            ->get()
            ->map(fn ($s) => ['id' => $s->id, 'full_name' => $s->full_name])
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
                $scoresMap[$score->student_id][$score->score_head_id] = rtrim(rtrim((string) $score->score, '0'), '.');
            }
        }

        $this->scores = $scoresMap;
        $this->loaded = true;
    }

    public function saveScores(): void
    {
        $user = Auth::user();

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id || ! $this->subject_id) {
            Notification::make()->title('Please select all filters before saving.')->warning()->send();
            return;
        }

        // ── Server-side re-authorization (never trust frontend state) ─────────
        if (! $this->canEnterScoresFor($user, $this->subject_id, $this->classroom_id, $this->session_id, $this->term_id)) {
            Notification::make()->title('Unauthorised: you cannot enter scores for this subject/class.')->danger()->send();
            return;
        }

        $validScoreHeadIds = collect($this->scoreHeads)->pluck('id')->toArray();

        // ── Validate all values ───────────────────────────────────────────────
        foreach ($this->scores as $studentId => $studentScores) {
            foreach ($studentScores as $scoreHeadId => $value) {
                if ($value === '' || $value === null) {
                    continue;
                }

                // Score head must belong to the class structure
                if (! in_array($scoreHeadId, $validScoreHeadIds, true)) {
                    throw ValidationException::withMessages(['scores' => 'Invalid score head submitted.']);
                }

                $sh = collect($this->scoreHeads)->firstWhere('id', $scoreHeadId);
                $numericValue = (float) $value;

                if ($numericValue < 0 || $numericValue > $sh['effective_max']) {
                    throw ValidationException::withMessages([
                        'scores' => "Score for \"{$sh['name']}\" must be between 0 and {$sh['effective_max']}.",
                    ]);
                }
            }
        }

        // ── Persist in a single transaction ──────────────────────────────────
        DB::transaction(function () use ($user) {
            foreach ($this->scores as $studentId => $studentScores) {
                foreach ($studentScores as $scoreHeadId => $value) {
                    if ($value === '' || $value === null) {
                        // Remove existing score when cell is cleared
                        Score::where([
                            'student_id'    => $studentId,
                            'subject_id'    => $this->subject_id,
                            'score_head_id' => $scoreHeadId,
                            'session_id'    => $this->session_id,
                            'term_id'       => $this->term_id,
                        ])->delete();
                        continue;
                    }

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
                            'score'        => (float) $value,
                        ]
                    );
                }
            }
        });

        Notification::make()->title('Scores saved successfully.')->success()->send();
    }

    // ── Computed properties ───────────────────────────────────────────────────

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

        // Class teacher → all subjects that have assignments in this class/term
        if (ClassTeacherAssignment::isClassTeacher($user->id, $this->classroom_id, $this->session_id)) {
            $subjectIds = TeacherSubjectAssignment::where('classroom_id', $this->classroom_id)
                ->where('session_id', $this->session_id)
                ->where('term_id',    $this->term_id)
                ->pluck('subject_id')
                ->unique();

            return Subject::whereIn('id', $subjectIds)->active()->orderBy('name')->get();
        }

        // Subject teacher → only their assigned subjects for this class
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
