<?php

namespace App\Http\Controllers;

use App\Models\MidtermClassTermApproval;
use App\Models\MidtermSubjectResult;
use App\Models\MidtermTermResult;
use App\Models\ResultPin;
use App\Models\Score;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TermScoringSnapshot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Facades\Impersonation;

class MidtermPdfController extends Controller
{
    public function download(Request $request)
    {
        if (Impersonation::isImpersonating()) {
            abort(403, 'Result downloads are disabled during administrative impersonation.');
        }

        $request->validate([
            'session_id' => 'required|integer',
            'term_id'    => 'required|integer',
            'student_id' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $sessionId = (int) $request->session_id;
        $termId    = (int) $request->term_id;

        if ($request->has('student_id')) {
            if ($user->canManageAcademics() || $user->isTeacher()) {
                $student = Student::findOrFail((int) $request->student_id);
            } else {
                if ((int) $request->student_id !== (int) $user->student?->id) {
                    abort(403, 'Unauthorized access to another student result.');
                }
                $student = $user?->student;
            }
        } else {
            $student = $user?->student;
        }

        if (! $student) {
            abort(403, 'No student profile linked.');
        }

        if (! $user->canManageAcademics() && ! $user->isTeacher()) {
            // Student Result PIN check
            $isUnlocked = ResultPin::where('student_id', $student->id)
                ->where('session_id', $sessionId)
                ->where('term_id', $termId)
                ->where('is_used', true)
                ->exists();

            if (! $isUnlocked) {
                abort(403, 'You have not unlocked results for this term. Please enter your result checker PIN on your student portal first.');
            }
        }

        $student->loadMissing('user');

        $term = Term::where('id', $termId)->where('session_id', $sessionId)->firstOrFail();

        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->with(['classroom', 'department', 'classArm'])
            ->first();

        $classroomId = $enrollment?->classroom_id;
        if (! $classroomId) {
            abort(404, 'Student enrollment not found for this session.');
        }

        // Approval check for students
        if (! $user->canManageAcademics() && ! $user->isTeacher()) {
            $approval = MidtermClassTermApproval::where('classroom_id', $classroomId)
                ->where('session_id', $sessionId)
                ->where('term_id', $termId)
                ->first();

            if (! $approval || ! $approval->is_published) {
                abort(403, 'Mid-term results for this term have not yet been published by the administration.');
            }
        }

        $snapshot = TermScoringSnapshot::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->firstOrFail();

        if ($snapshot->mode !== 'midterm') {
            abort(400, 'This class was not evaluated under the mid-term system for this term.');
        }

        $midtermTermResult = MidtermTermResult::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        $subjectResults = MidtermSubjectResult::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->with('subject')
            ->get();

        $midtermHeadIds = collect($snapshot->midterm_heads)->pluck('id')->toArray();

        $allScores = Score::where('student_id', $student->id)
            ->where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('score_head_id', $midtermHeadIds)
            ->get()
            ->groupBy('subject_id');

        $subjectRows = [];
        foreach ($subjectResults as $sr) {
            $subjectScores = $allScores->get($sr->subject_id, collect());
            $headScores = [];

            foreach ($snapshot->midterm_heads as $head) {
                $scoreRec = $subjectScores->firstWhere('score_head_id', $head['id']);
                if ($scoreRec === null) {
                    $headScores[$head['id']] = '—';
                } elseif ($scoreRec->is_absent) {
                    $headScores[$head['id']] = 'ABS';
                } else {
                    $headScores[$head['id']] = (float) $scoreRec->score;
                }
            }

            $isAbsent = ($sr->status === 'absent');
            $isMissing = ($sr->status === 'missing');

            $subjectRows[] = [
                'subject'         => $sr->subject?->name ?? 'Unknown',
                'head_scores'     => $headScores,
                'raw_score'       => ($isAbsent || $isMissing) ? '—' : (float) $sr->raw_score,
                'student_raw_max' => $sr->student_raw_max,
                'scaled_score'    => $isAbsent ? 'ABS' : ($isMissing ? '—' : (float) $sr->scaled_score),
                'percentage'      => $isAbsent ? 'ABS' : ($isMissing ? '—' : (float) $sr->percentage),
                'grade'           => $sr->grade,
                'remark'          => $sr->remark,
            ];
        }

        $session = Session::find($sessionId);
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        $classSize = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->count();

        $armPopulation = !empty($enrollment?->class_arm_id)
            ? StudentEnrollment::where('classroom_id', $classroomId)
                ->where('session_id', $sessionId)
                ->where('class_arm_id', $enrollment->class_arm_id)
                ->count()
            : null;

        $departmentPopulation = !empty($enrollment?->department_id)
            ? StudentEnrollment::where('classroom_id', $classroomId)
                ->where('session_id', $sessionId)
                ->where('department_id', $enrollment->department_id)
                ->count()
            : null;

        $data = [
            'report_title'          => strtoupper($term->name) . ' MID-TERM ACADEMIC PROGRESS REPORT',
            'student'               => [
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
            'midterm_weight'        => $snapshot->midterm_weight,
            'raw_max'               => $snapshot->raw_max,
            'midterm_heads'         => $snapshot->midterm_heads,
            'subjects'              => $subjectRows,
            'term_result'           => $midtermTermResult ? [
                'subjects_count'        => $midtermTermResult->subjects_count,
                'total_obtainable'      => (float) $midtermTermResult->total_obtainable,
                'total_obtained'        => (float) $midtermTermResult->total_obtained,
                'percentage'            => (float) $midtermTermResult->percentage,
                'grade'                 => $midtermTermResult->grade,
                'remark'                => $midtermTermResult->remark,
                'class_population'      => $classSize,
                'arm_population'        => $armPopulation,
                'department_population' => $departmentPopulation,
                'teacher_comment'       => $midtermTermResult->teacher_comment,
                'head_teacher_comment'  => $midtermTermResult->head_teacher_comment,
            ] : null,
            'settings'              => $settings,
        ];

        $pdf = Pdf::loadView('results.midterm-slip', ['data' => $data])
            ->setPaper('a4', 'portrait');

        $studentName = str_replace(' ', '_', trim($student->name));
        $termName    = str_replace(' ', '_', $term->name);

        return $pdf->download("{$studentName}_{$termName}_midterm_slip.pdf");
    }
}
