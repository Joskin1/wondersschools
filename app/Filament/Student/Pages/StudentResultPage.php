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

        // ── Get score heads (dynamic columns) ────────────────────────────────
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

        // ── Get subject results with individual scores ───────────────────────
        $subjectResults = SubjectResult::where('student_id', $student->id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->with('subject')
            ->get();

        $subjectRows = [];
        foreach ($subjectResults as $sr) {
            $row = [
                'subject'  => $sr->subject->name ?? 'Unknown',
                'total'    => $sr->total,
                'grade'    => $sr->grade,
                'position' => $sr->position,
                'remark'   => $sr->remark,
                'scores'   => [],
            ];

            // Fetch individual scores per score head
            foreach ($scoreHeads as $sh) {
                $score = Score::where('student_id', $student->id)
                    ->where('subject_id', $sr->subject_id)
                    ->where('score_head_id', $sh['id'])
                    ->where('session_id', $this->session_id)
                    ->where('term_id', $this->term_id)
                    ->first();

                $row['scores'][$sh['id']] = $score ? $score->score : '-';
            }

            $subjectRows[] = $row;
        }

        // ── Session & term names ─────────────────────────────────────────────
        $session = Session::find($this->session_id);

        // ── Class size ───────────────────────────────────────────────────────
        $classSize = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $this->session_id)
            ->count();

        // ── Total obtainable ─────────────────────────────────────────────────
        $totalObtainable = ($structure?->total_score ?? 100) * count($subjectRows);

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
