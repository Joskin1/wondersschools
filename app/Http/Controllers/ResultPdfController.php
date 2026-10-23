<?php

namespace App\Http\Controllers;

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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Facades\Impersonation;

class ResultPdfController extends Controller
{
    public function download(Request $request)
    {
        if (Impersonation::isImpersonating()) {
            abort(403, 'Result downloads are disabled during administrative impersonation.');
        }

        $request->validate([
            'session_id' => 'required|integer',
            'term_id'    => 'required|integer',
        ]);

        $student = Auth::user()->student;

        if (! $student) {
            abort(403, 'No student profile linked.');
        }

        $student->loadMissing('user');

        $sessionId = (int) $request->session_id;
        $termId    = (int) $request->term_id;

        // Validate term belongs to session
        $term = Term::where('id', $termId)->where('session_id', $sessionId)->firstOrFail();

        // Get term result (scoped to this student and must be finalized)
        $termResult = TermResult::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->where('is_finalized', true)
            ->firstOrFail();

        // ── Verify Result Checker PIN Gate ──────────────────────────────────
        $isUnlocked = ResultPin::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->where('is_used', true)
            ->exists();

        if (! $isUnlocked) {
            abort(403, 'You have not unlocked results for this term. Please enter your result checker PIN on your student portal first.');
        }

        // Enrollment for class info
        $enrollment  = StudentEnrollment::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->with(['classroom', 'department', 'classArm'])
            ->first();
        $classroomId = $enrollment?->classroom_id ?? $termResult->classroom_id;

        // ── Admin Approval Gate ──────────────────────────────────────────────
        $approval = \App\Models\ClassTermApproval::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        if ($approval && $approval->status !== 'approved') {
            abort(403, 'Results for this term are pending final administrative approval and have not yet been released.');
        }

        // ── Detect cumulative (third term) ───────────────────────────────────
        $isCumulative = $term->order === 3;

        // Score heads & snapshot detection
        $snapshot = \App\Models\TermScoringSnapshot::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        $isMidterm = ($snapshot && $snapshot->mode === 'midterm');

        $structure = ClassScoreStructure::where('class_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        $scoreHeads = [];
        if ($isMidterm) {
            $scoreHeads[] = [
                'id'   => 'midterm',
                'name' => 'Mid-Term (' . $snapshot->midterm_weight . '%)',
            ];
            foreach ($snapshot->exam_heads as $eh) {
                $scoreHeads[] = [
                    'id'   => $eh['id'],
                    'name' => $eh['name'],
                ];
            }
        } elseif ($structure) {
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

        // Pre-fetch count of students examined per subject in this classroom/session/term
        $studentsExaminedCounts = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->selectRaw('subject_id, count(distinct student_id) as examined_count')
            ->groupBy('subject_id')
            ->pluck('examined_count', 'subject_id');

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

                if ($isMidterm) {
                    if ($sr->midterm_status === 'absent') {
                        $midtermVal = 'ABS';
                    } elseif ($sr->midterm_status === 'missing') {
                        $midtermVal = '—';
                    } else {
                        $midtermVal = $sr->midterm_score !== null ? (float) $sr->midterm_score : '—';
                    }
                    $scores['midterm'] = $midtermVal;
                }

                foreach ($scoreHeads as $sh) {
                    if ($sh['id'] === 'midterm') {
                        continue;
                    }
                    $s = $subjectScores->firstWhere('score_head_id', $sh['id']);
                    $val = $s ? ($s->is_absent ? 'ABS' : $s->score) : 0;
                    $scores[$sh['id']] = $s ? ($s->is_absent ? 'ABS' : $s->score) : '-';
                    $t3Raw += (float) ($s && ! $s->is_absent ? $s->score : 0);
                }

                if ($isMidterm) {
                    $midtermPart = ($sr->midterm_status === 'absent' || $sr->midterm_status === 'missing') ? 0.0 : (float) ($sr->midterm_score ?? 0);
                    $t3Raw = round($midtermPart + $t3Raw, 2);
                } else {
                    $t3Raw = round($t3Raw, 2);
                }

                $subjectRows[] = [
                    'subject'           => $sr->subject->name ?? 'Unknown',
                    'scores'            => $scores,
                    'term3_raw'         => $t3Raw,
                    'term1'             => $t1,
                    'term2'             => $t2,
                    'average'           => (float) $sr->total,
                    'grade'             => $sr->grade,
                    'position'          => $sr->position,
                    'remark'            => $sr->remark,
                    'students_examined' => $studentsExaminedCounts->get($subjectId, 0),
                ];
            }
        } else {
            // ── Standalone path ──────────────────────────────────────────────
            foreach ($subjectResults as $sr) {
                $scores = [];
                $subjectScores = $allScores->get($sr->subject_id, collect());

                if ($isMidterm) {
                    if ($sr->midterm_status === 'absent') {
                        $midtermVal = 'ABS';
                    } elseif ($sr->midterm_status === 'missing') {
                        $midtermVal = '—';
                    } else {
                        $midtermVal = $sr->midterm_score !== null ? (float) $sr->midterm_score : '—';
                    }
                    $scores['midterm'] = $midtermVal;
                }

                foreach ($scoreHeads as $sh) {
                    if ($sh['id'] === 'midterm') {
                        continue;
                    }
                    $s = $subjectScores->firstWhere('score_head_id', $sh['id']);
                    $scores[$sh['id']] = $s ? ($s->is_absent ? 'ABS' : $s->score) : '-';
                }

                $subjectRows[] = [
                    'subject'           => $sr->subject->name ?? 'Unknown',
                    'total'             => $sr->total,
                    'grade'             => $sr->grade,
                    'position'          => $sr->position,
                    'remark'            => $sr->remark,
                    'scores'            => $scores,
                    'students_examined' => $studentsExaminedCounts->get($sr->subject_id, 0),
                ];
            }
        }

        $session         = Session::find($sessionId);
        $classSize       = StudentEnrollment::where('classroom_id', $classroomId)->where('session_id', $sessionId)->count();
        $armPopulation   = $termResult->arm_population ?? (
            !empty($enrollment?->class_arm_id)
                ? StudentEnrollment::where('classroom_id', $classroomId)->where('session_id', $sessionId)->where('class_arm_id', $enrollment->class_arm_id)->count()
                : null
        );
        $departmentPopulation = $termResult->department_population ?? (
            !empty($enrollment?->department_id)
                ? StudentEnrollment::where('classroom_id', $classroomId)->where('session_id', $sessionId)->where('department_id', $enrollment->department_id)->count()
                : null
        );
        $totalObtainable = ($isCumulative || $isMidterm)
            ? 100 * count($subjectRows)
            : ($structure?->total_score ?? 100) * count($subjectRows);
        $rcDefaults      = \App\Services\ReportCardSettingsService::defaults();
        $storedSettings  = Setting::all()->pluck('value', 'key')->toArray();
        $settings        = array_merge($rcDefaults, $storedSettings);
        foreach ($rcDefaults as $k => $v) {
            if (is_bool($v) && isset($settings[$k])) {
                $settings[$k] = filter_var($settings[$k], FILTER_VALIDATE_BOOLEAN);
            }
        }
        $marginCss       = \App\Services\ReportCardSettingsService::getMarginCss($settings);

        $data = [
            'student'      => [
                'name'           => $student->name,
                'gender'         => $student->gender ?? '-',
                'dob'            => $student->date_of_birth?->format('d/m/Y') ?? '-',
                'admission_no'   => $student->admission_number ?? '-',
                'department'     => $enrollment?->department?->name ?? null,
                'arm'            => $enrollment?->classArm?->name ?? null,
                'passport_photo' => $student->profile_picture,
            ],
            'classroom'             => $enrollment?->classroom?->name ?? '-',
            'class_size'            => $classSize,
            'arm_population'        => $armPopulation,
            'department_population' => $departmentPopulation,
            'session_name'          => $session?->name ?? '-',
            'term_name'             => $term->name ?? '-',
            'is_cumulative'         => $isCumulative,
            'score_heads'           => $scoreHeads,
            'subjects'              => $subjectRows,
            'times_school_opened'   => $approval?->times_school_opened,
            'next_term_begins'      => $approval?->next_term_begins,
            'term_result'           => [
                'subjects_count'        => $termResult->subjects_count,
                'grand_total'           => $termResult->grand_total,
                'average'               => $termResult->average,
                'grade'                 => $termResult->grade,
                'remark'                => $termResult->remark,
                'overall_position'      => $termResult->overall_position,
                'class_population'      => $termResult->class_population ?? $classSize,
                'department_position'   => $termResult->department_position,
                'department_population' => $departmentPopulation,
                'arm_position'          => $termResult->arm_position,
                'arm_population'        => $armPopulation,
                'times_present'         => $termResult->times_present,
                'class_teacher_comment' => $termResult->class_teacher_comment,
                'principal_comment'     => $termResult->principal_comment,
            ],
            'total_obtainable'      => $totalObtainable,
            'settings'              => $settings,
            'margin_css'            => $marginCss,
        ];

        $pdf = Pdf::loadView('results.result-sheet', ['data' => $data])
            ->setPaper('a4', 'portrait');

        $studentName = str_replace(' ', '_', trim($student->name));
        $termName    = str_replace(' ', '_', $term->name);

        return $pdf->download("{$studentName}_{$termName}_result.pdf");
    }
}
