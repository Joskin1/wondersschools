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

        // Score heads
        $structure  = ClassScoreStructure::where('class_id', $classroomId)
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
            foreach ($scoreHeads as $sh) {
                $score = Score::where('student_id', $student->id)
                    ->where('subject_id', $sr->subject_id)
                    ->where('score_head_id', $sh['id'])
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->first();
                $row['scores'][$sh['id']] = $score ? $score->score : '-';
            }
            $subjectRows[] = $row;
        }

        $session        = Session::find($sessionId);
        $classSize      = StudentEnrollment::where('classroom_id', $classroomId)->where('session_id', $sessionId)->count();
        $totalObtainable = ($structure?->total_score ?? 100) * count($subjectRows);
        $settings       = Setting::all()->pluck('value', 'key')->toArray();

        $data = [
            'student'      => [
                'name'   => $student->full_name,
                'gender' => $student->profile?->gender ?? '-',
                'dob'    => $student->profile?->date_of_birth?->format('d/m/Y') ?? '-',
            ],
            'classroom'        => $enrollment?->classroom?->name ?? '-',
            'session_name'     => $session?->name ?? '-',
            'term_name'        => $term->name ?? '-',
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
