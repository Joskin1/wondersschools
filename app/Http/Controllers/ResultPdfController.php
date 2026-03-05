<?php

namespace App\Http\Controllers;

use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\Score;
use App\Models\Session;
use App\Models\Setting;
use App\Models\StudentEnrollment;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\ResultCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultPdfController extends Controller
{
    public function download(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer',
            'term_id'    => 'required|integer',
        ]);

        $student = Auth::user()->student;

        if (! $student) {
            abort(403, 'No student profile linked.');
        }

        $sessionId = (int) $request->session_id;
        $termId    = (int) $request->term_id;

        // Validate term belongs to session
        $term = Term::where('id', $termId)->where('session_id', $sessionId)->firstOrFail();

        // Get term result (scoped to this student)
        $termResult = TermResult::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->firstOrFail();

        // Enrollment for class info
        $enrollment  = StudentEnrollment::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->with('classroom')
            ->first();
        $classroomId = $enrollment?->classroom_id ?? $termResult->classroom_id;

        // ── Detect cumulative (third term) ───────────────────────────────────
        $isCumulative = $term->order === 3;

        // Score heads — always loaded
        $structure = ClassScoreStructure::where('class_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
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

        // Subject results
        $subjectResults = SubjectResult::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->with('subject')
            ->get();

        // Pre-fetch all scores in one query (avoid N+1)
        $allScores = Score::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->get()
            ->groupBy('subject_id');

        $subjectRows = [];

        if ($isCumulative) {
            // ── Cumulative path: score heads + cross-term totals ──────────────
            $service = app(ResultCalculationService::class);
            $termIds = $service->resolveTermIds($sessionId);

            $priorResults = [];
            if ($termIds) {
                $priorRows = SubjectResult::where('student_id', $student->id)
                    ->where('session_id', $sessionId)
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
            // ── Standalone path ──────────────────────────────────────────────
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

        $session         = Session::find($sessionId);
        $classSize       = StudentEnrollment::where('classroom_id', $classroomId)->where('session_id', $sessionId)->count();
        $totalObtainable = $isCumulative
            ? 100 * count($subjectRows)
            : ($structure?->total_score ?? 100) * count($subjectRows);
        $settings        = Setting::all()->pluck('value', 'key')->toArray();

        $data = [
            'student'      => [
                'name'   => $student->full_name,
                'gender' => $student->profile?->gender ?? '-',
                'dob'    => $student->profile?->date_of_birth?->format('d/m/Y') ?? '-',
            ],
            'classroom'        => $enrollment?->classroom?->name ?? '-',
            'session_name'     => $session?->name ?? '-',
            'term_name'        => $term->name ?? '-',
            'is_cumulative'    => $isCumulative,
            'score_heads'      => $scoreHeads,
            'subjects'         => $subjectRows,
            'term_result'      => [
                'subjects_count'   => $termResult->subjects_count,
                'grand_total'      => $termResult->grand_total,
                'average'          => $termResult->average,
                'grade'            => $termResult->grade,
                'remark'           => $termResult->remark,
                'overall_position' => $termResult->overall_position,
            ],
            'class_size'       => $classSize,
            'total_obtainable' => $totalObtainable,
            'settings'         => $settings,
        ];

        $pdf = Pdf::loadView('results.result-sheet', ['data' => $data])
            ->setPaper('a4', 'portrait');

        $studentName = str_replace(' ', '_', trim($student->full_name));
        $termName    = str_replace(' ', '_', $term->name);

        return $pdf->download("{$studentName}_{$termName}_result.pdf");
    }
}
