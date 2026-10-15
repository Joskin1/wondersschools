<?php

namespace App\Filament\Student\Pages;

use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\ResultPin;
use App\Models\Score;
use App\Models\Session;
use App\Models\Setting;
use App\Models\StudentEnrollment;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\ResultCalculationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Facades\Impersonation;

class StudentResultPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'My Results';

    protected static ?string $title = 'My Results';

    protected static ?string $slug = 'my-results';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.student.pages.student-result-page';

    // ── Filters ──────────────────────────────────────────────────────────────
    public ?int $session_id = null;

    public ?int $term_id = null;

    // ── PIN & Impersonation Gatekeeper ───────────────────────────────────────
    public bool $isUnlocked = false;

    public bool $isImpersonating = false;

    public string $pin_input = '';

    public ?string $pin_error = null;

    // ── Loaded data ──────────────────────────────────────────────────────────
    public array $sessions = [];

    public array $terms = [];

    public bool $loaded = false;

    public array $resultData = [];

    public function mount(): void
    {
        $this->isImpersonating = Impersonation::isImpersonating();

        $student = Auth::user()->student;

        if (! $student) {
            return;
        }

        // Load sessions where this student has finalized results.
        $sessionIds = TermResult::query()
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->pluck('session_id')
            ->unique();

        $this->sessions = Session::whereIn('id', $sessionIds)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
            ->toArray();
    }

    public function updatedSessionId(): void
    {
        $this->term_id = null;
        $this->loaded = false;
        $this->isUnlocked = false;
        $this->pin_input = '';
        $this->pin_error = null;
        $this->resultData = [];
        $this->terms = [];

        if (! $this->session_id) {
            return;
        }

        $termIds = TermResult::query()
            ->where('student_id', Auth::user()->student?->id)
            ->where('session_id', $this->session_id)
            ->where('is_finalized', true)
            ->pluck('term_id')
            ->unique();

        $this->terms = Term::where('session_id', $this->session_id)
            ->whereIn('id', $termIds)
            ->orderBy('order')
            ->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->toArray();
    }

    public function updatedTermId(): void
    {
        $this->loaded = false;
        $this->isUnlocked = false;
        $this->pin_input = '';
        $this->pin_error = null;
        $this->resultData = [];

        if (! $this->session_id || ! $this->term_id) {
            return;
        }

        $this->isImpersonating = Impersonation::isImpersonating();

        if ($this->isImpersonating) {
            return;
        }

        $student = Auth::user()->student;

        if (! $student) {
            return;
        }

        // Check if student has already unlocked this term result
        $this->isUnlocked = ResultPin::where('student_id', $student->id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('is_used', true)
            ->exists();

        if ($this->isUnlocked) {
            $this->loadResult();
        }
    }

    public function verifyPin(): void
    {
        $this->pin_error = null;

        $this->isImpersonating = Impersonation::isImpersonating();
        if ($this->isImpersonating) {
            $this->pin_error = 'Action not permitted during administrative impersonation.';

            return;
        }

        $student = Auth::user()->student;
        if (! $student || ! $this->session_id || ! $this->term_id) {
            $this->pin_error = 'Invalid request. Please select session and term first.';

            return;
        }

        $cleanPin = trim($this->pin_input);

        if (empty($cleanPin)) {
            $this->pin_error = 'Please enter your 6-digit PIN.';

            return;
        }

        if (! preg_match('/^\d{6}$/', $cleanPin)) {
            $this->pin_error = 'The PIN must be exactly 6 digits.';

            return;
        }

        $pinRecord = ResultPin::where('pin', $cleanPin)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->first();

        if (! $pinRecord) {
            $this->pin_error = 'Invalid PIN. Please check the 6 digits on your scratch card and ensure it matches this session and term.';

            return;
        }

        if ($pinRecord->is_used) {
            if ($pinRecord->student_id === $student->id) {
                $this->isUnlocked = true;
                $this->pin_input = '';
                $this->loadResult();

                return;
            }

            $this->pin_error = 'This PIN has already been used by another student.';

            return;
        }

        // Redeem the PIN
        $pinRecord->update([
            'is_used'    => true,
            'student_id' => $student->id,
            'used_at'    => now(),
        ]);

        $this->isUnlocked = true;
        $this->pin_input = '';
        $this->pin_error = null;

        Notification::make()
            ->title('Result Unlocked!')
            ->body('Your scratch card PIN has been successfully redeemed.')
            ->success()
            ->send();

        $this->loadResult();
    }

    public function loadResult(): void
    {
        if ($this->isImpersonating || ! $this->isUnlocked) {
            $this->resultData = [];
            $this->loaded = false;

            return;
        }

        $student = Auth::user()->student;

        if (! $student) {
            return;
        }

        // ── Validate term belongs to session ─────────────────────────────────
        $term = Term::where('id', $this->term_id)
            ->where('session_id', $this->session_id)
            ->first();

        if (! $term) {
            $this->resultData = [];
            $this->loaded = false;

            return;
        }

        // ── Get term result ──────────────────────────────────────────────────
        $termResult = TermResult::where('student_id', $student->id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('is_finalized', true)
            ->first();

        if (! $termResult) {
            $this->resultData = [];
            $this->loaded = false;

            return;
        }

        // ── Get enrollment for class info ────────────────────────────────────
        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('session_id', $this->session_id)
            ->with(['classroom', 'department', 'classArm'])
            ->first();

        $classroomId = $enrollment?->classroom_id ?? $termResult->classroom_id;

        // ── Admin Approval Gate ──────────────────────────────────────────────
        $approval = \App\Models\ClassTermApproval::where('classroom_id', $classroomId)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->first();

        if ($approval && $approval->status !== 'approved') {
            $this->resultData = [];
            $this->loaded = false;

            return;
        }

        // ── Detect third term (cumulative) ───────────────────────────────────
        $isCumulative = $term->order === 3;

        // ── Get score heads (dynamic columns) — always loaded ────────────────
        $structure = ClassScoreStructure::where('class_id', $classroomId)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->first();

        $scoreHeads = [];
        if ($structure) {
            $scoreHeads = ClassScoreStructureItem::where('class_score_structure_id', $structure->id)
                ->join('score_heads', 'score_heads.id', '=', 'class_score_structure_items.score_head_id')
                ->select('score_heads.id', 'score_heads.name')
                ->orderBy('score_heads.id')
                ->get()
                ->toArray();
        }

        // ── Get subject results ──────────────────────────────────────────────
        $subjectResults = SubjectResult::where('student_id', $student->id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->with('subject')
            ->get();

        // ── Pre-fetch all term 3 scores in one query (avoid N+1) ─────────────
        $allScores = Score::where('student_id', $student->id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->get()
            ->groupBy('subject_id');

        $subjectRows = [];

        if ($isCumulative) {
            // ── Cumulative path: score heads + cross-term totals ─────────────
            $service = app(ResultCalculationService::class);
            $termIds = $service->resolveTermIds($this->session_id);

            // Pre-fetch prior term results for this student
            $priorResults = [];
            if ($termIds) {
                $priorRows = SubjectResult::where('student_id', $student->id)
                    ->where('session_id', $this->session_id)
                    ->whereIn('term_id', [$termIds[1], $termIds[2]])
                    ->get();

                foreach ($priorRows as $pr) {
                    $priorResults[$pr->subject_id][$pr->term_id] = (float) $pr->total;
                }
            }

            foreach ($subjectResults as $sr) {
                $subjectId = $sr->subject_id;
                $t1 = $priorResults[$subjectId][$termIds[1] ?? 0] ?? 0;
                $t2 = $priorResults[$subjectId][$termIds[2] ?? 0] ?? 0;

                // Per-score-head breakdown for term 3
                $scores = [];
                $t3Raw = 0;
                $subjectScores = $allScores->get($subjectId, collect());
                foreach ($scoreHeads as $sh) {
                    $s = $subjectScores->firstWhere('score_head_id', $sh['id']);
                    $val = $s ? $s->score : 0;
                    $scores[$sh['id']] = $val;
                    $t3Raw += (float) $val;
                }
                $t3Raw = round($t3Raw, 2);

                $subjectRows[] = [
                    'subject' => $sr->subject->name ?? 'Unknown',
                    'scores' => $scores,
                    'term3_raw' => $t3Raw,
                    'term1' => $t1,
                    'term2' => $t2,
                    'average' => (float) $sr->total,
                    'grade' => $sr->grade,
                    'position' => $sr->position,
                    'remark' => $sr->remark,
                ];
            }
        } else {
            // ── Standalone path (Term 1 & 2): existing logic ─────────────────
            foreach ($subjectResults as $sr) {
                $scores = [];
                $subjectScores = $allScores->get($sr->subject_id, collect());
                foreach ($scoreHeads as $sh) {
                    $s = $subjectScores->firstWhere('score_head_id', $sh['id']);
                    $scores[$sh['id']] = $s ? $s->score : '-';
                }

                $subjectRows[] = [
                    'subject' => $sr->subject->name ?? 'Unknown',
                    'total' => $sr->total,
                    'grade' => $sr->grade,
                    'position' => $sr->position,
                    'remark' => $sr->remark,
                    'scores' => $scores,
                ];
            }
        }

        // ── Session & term names ─────────────────────────────────────────────
        $session = Session::find($this->session_id);

        // ── Class size ───────────────────────────────────────────────────────
        $classSize = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $this->session_id)
            ->count();

        // ── Total obtainable ─────────────────────────────────────────────────
        if ($isCumulative) {
            // For cumulative, max possible average is 100 × number_of_subjects
            $totalObtainable = 100 * count($subjectRows);
        } else {
            $structure = $structure ?? ClassScoreStructure::where('class_id', $classroomId)
                ->where('session_id', $this->session_id)
                ->where('term_id', $this->term_id)
                ->first();
            $totalObtainable = ($structure?->total_score ?? 100) * count($subjectRows);
        }

        // ── School settings ──────────────────────────────────────────────────
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        $this->resultData = [
            'student' => [
                'name'         => $student->full_name,
                'gender'       => $student->gender ?? '-',
                'dob'          => $student->date_of_birth?->format('d/m/Y') ?? '-',
                'admission_no' => $student->admission_number ?? '-',
                'department'   => $enrollment?->department?->name ?? null,
                'arm'          => $enrollment?->classArm?->name ?? null,
            ],
            'classroom' => $enrollment?->classroom?->name ?? '-',
            'session_name' => $session?->name ?? '-',
            'term_name' => $term->name ?? '-',
            'is_cumulative' => $isCumulative,
            'score_heads' => $scoreHeads,
            'subjects' => $subjectRows,
            'times_school_opened' => $approval?->times_school_opened,
            'next_term_begins'    => $approval?->next_term_begins,
            'term_result' => [
                'subjects_count'        => $termResult->subjects_count,
                'grand_total'           => $termResult->grand_total,
                'average'               => $termResult->average,
                'grade'                 => $termResult->grade,
                'remark'                => $termResult->remark,
                'overall_position'      => $termResult->overall_position,
                'class_population'      => $termResult->class_population ?? $classSize,
                'department_position'   => $termResult->department_position,
                'department_population' => $termResult->department_population,
                'arm_position'          => $termResult->arm_position,
                'arm_population'        => $termResult->arm_population,
                'times_present'         => $termResult->times_present,
                'class_teacher_comment' => $termResult->class_teacher_comment,
                'principal_comment'     => $termResult->principal_comment,
            ],
            'class_size' => $classSize,
            'total_obtainable' => $totalObtainable,
            'settings' => $settings,
        ];

        $this->loaded = true;
    }
}
