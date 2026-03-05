<?php

namespace App\Filament\Student\Pages;

use App\Models\Score;
use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\Session;
use App\Models\Setting;
use App\Models\StudentEnrollment;
use App\Models\SubjectResult;
use App\Models\TermResult;
use App\Services\ResultCalculationService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class StudentResultPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'My Results';

    protected static ?string $title = 'My Results';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.student.pages.student-result-page';

    // ── Filters ──────────────────────────────────────────────────────────────
    public ?int $session_id = null;
    public ?int $term_id    = null;

    // ── Loaded data ──────────────────────────────────────────────────────────
    public array $sessions   = [];
    public array $terms      = [];
    public bool  $loaded     = false;
    public array $resultData = [];

    public function mount(): void
    {
        $student = Auth::user()->student;

        if (! $student) {
            return;
        }

        // Only load sessions where term_results exist for this student
        $sessionIds = TermResult::where('student_id', $student->id)
            ->distinct()
            ->pluck('session_id');

        $this->sessions = Session::whereIn('id', $sessionIds)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
            ->toArray();
    }

    public function updatedSessionId(): void
    {
        $this->term_id = null;
        $this->loaded  = false;
        $this->resultData = [];
        $this->terms   = [];

        if (! $this->session_id) {
            return;
        }

        $student = Auth::user()->student;

        $termIds = TermResult::where('student_id', $student->id)
            ->where('session_id', $this->session_id)
            ->distinct()
            ->pluck('term_id');

        $this->terms = \App\Models\Term::whereIn('id', $termIds)
            ->orderBy('order')
            ->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->toArray();
    }

    public function updatedTermId(): void
    {
        $this->loaded     = false;
        $this->resultData = [];

        if (! $this->session_id || ! $this->term_id) {
            return;
        }

        $this->loadResult();
    }

    public function loadResult(): void
    {
        $student = Auth::user()->student;

        if (! $student) {
            return;
        }

        // ── Validate term belongs to session ─────────────────────────────────
        $term = \App\Models\Term::where('id', $this->term_id)
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
            ->first();

        if (! $termResult) {
            $this->resultData = [];
            $this->loaded = false;
            return;
        }

        // ── Get enrollment for class info ────────────────────────────────────
        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('session_id', $this->session_id)
            ->with('classroom')
            ->first();

        $classroomId = $enrollment?->classroom_id ?? $termResult->classroom_id;

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
                    'subject'   => $sr->subject->name ?? 'Unknown',
                    'scores'    => $scores,
                    'term3_raw' => $t3Raw,
                    'term1'     => $t1,
                    'term2'     => $t2,
                    'average'   => (float) $sr->total,
                    'grade'     => $sr->grade,
                    'position'  => $sr->position,
                    'remark'    => $sr->remark,
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
                    'subject'  => $sr->subject->name ?? 'Unknown',
                    'total'    => $sr->total,
                    'grade'    => $sr->grade,
                    'position' => $sr->position,
                    'remark'   => $sr->remark,
                    'scores'   => $scores,
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
            'student'      => [
                'name'     => $student->full_name,
                'gender'   => $student->profile?->gender ?? '-',
                'dob'      => $student->profile?->date_of_birth?->format('d/m/Y') ?? '-',
            ],
            'classroom'    => $enrollment?->classroom?->name ?? '-',
            'session_name' => $session?->name ?? '-',
            'term_name'    => $term->name ?? '-',
            'is_cumulative' => $isCumulative,
            'score_heads'  => $scoreHeads,
            'subjects'     => $subjectRows,
            'term_result'  => [
                'subjects_count'   => $termResult->subjects_count,
                'grand_total'      => $termResult->grand_total,
                'average'          => $termResult->average,
                'grade'            => $termResult->grade,
                'remark'           => $termResult->remark,
                'overall_position' => $termResult->overall_position,
            ],
            'class_size'        => $classSize,
            'total_obtainable'  => $totalObtainable,
            'settings'          => $settings,
        ];

        $this->loaded = true;
    }
}
