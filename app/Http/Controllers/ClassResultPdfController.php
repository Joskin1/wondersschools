<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\ClassResultPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Facades\Impersonation;

class ClassResultPdfController extends Controller
{
    protected ClassResultPdfService $pdfService;

    public function __construct(ClassResultPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Ensure only administrators have access to batch result printing.
     */
    protected function authorizeAdmin(): void
    {
        if (Impersonation::isImpersonating()) {
            abort(403, 'Result downloads are disabled during administrative impersonation.');
        }

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->canManageAcademics() && ! $user->isAdmin() && ! $user->isSudo()) {
            abort(403, 'Unauthorized access. Only school administrators are permitted to download batch class results.');
        }
    }

    /**
     * Download or stream consolidated Terminal Results PDF for an entire class cohort.
     */
    public function downloadTerminalClassPdf(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'session_id'   => 'required|integer',
            'term_id'      => 'required|integer',
            'classroom_id' => 'required|integer',
            'class_arm_id' => 'nullable|integer',
            'department_id'=> 'nullable|integer',
        ]);

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $classroomId   = (int) $request->classroom_id;
        $sessionId     = (int) $request->session_id;
        $termId        = (int) $request->term_id;
        $classArmId    = $request->filled('class_arm_id') ? (int) $request->class_arm_id : null;
        $departmentId  = $request->filled('department_id') ? (int) $request->department_id : null;

        $batchData = $this->pdfService->buildTerminalClassData(
            $classroomId,
            $sessionId,
            $termId,
            $classArmId,
            $departmentId
        );

        if (empty($batchData['studentsData'])) {
            abort(404, 'No processed student results found for this class in the selected session and term.');
        }

        $pdf = Pdf::loadView('results.class-result-sheets', $batchData)
            ->setPaper('a4', 'portrait');

        $className   = str_replace([' ', '/'], '_', trim($batchData['classroom']->name));
        $termName    = str_replace([' ', '/'], '_', trim($batchData['term']->name));
        $sessionName = str_replace([' ', '/'], '_', trim($batchData['session']->name));
        $filename    = "{$className}_{$termName}_{$sessionName}_Class_Results.pdf";

        if ($request->boolean('stream')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Download or stream consolidated Mid-Term Slips PDF for an entire class cohort.
     */
    public function downloadMidtermClassPdf(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'session_id'   => 'required|integer',
            'term_id'      => 'required|integer',
            'classroom_id' => 'required|integer',
            'class_arm_id' => 'nullable|integer',
            'department_id'=> 'nullable|integer',
        ]);

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $classroomId   = (int) $request->classroom_id;
        $sessionId     = (int) $request->session_id;
        $termId        = (int) $request->term_id;
        $classArmId    = $request->filled('class_arm_id') ? (int) $request->class_arm_id : null;
        $departmentId  = $request->filled('department_id') ? (int) $request->department_id : null;

        $batchData = $this->pdfService->buildMidtermClassData(
            $classroomId,
            $sessionId,
            $termId,
            $classArmId,
            $departmentId
        );

        if (empty($batchData['studentsData'])) {
            abort(404, 'No processed midterm results found for this class in the selected session and term.');
        }

        $pdf = Pdf::loadView('results.class-midterm-slips', $batchData)
            ->setPaper('a4', 'portrait');

        $className   = str_replace([' ', '/'], '_', trim($batchData['classroom']->name));
        $termName    = str_replace([' ', '/'], '_', trim($batchData['term']->name));
        $sessionName = str_replace([' ', '/'], '_', trim($batchData['session']->name));
        $filename    = "{$className}_{$termName}_{$sessionName}_Class_Midterm_Slips.pdf";

        if ($request->boolean('stream')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Download an individual student terminal report card as an Administrator.
     */
    public function downloadSingleStudentPdf(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'session_id' => 'required|integer',
            'term_id'    => 'required|integer',
            'student_id' => 'required|integer',
        ]);

        $sessionId = (int) $request->session_id;
        $termId    = (int) $request->term_id;
        $studentId = (int) $request->student_id;

        $enrollment = StudentEnrollment::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->firstOrFail();

        $batchData = $this->pdfService->buildTerminalClassData(
            $enrollment->classroom_id,
            $sessionId,
            $termId
        );

        $studentData = collect($batchData['studentsData'])->first(function ($item) use ($studentId) {
            return ($item['student']['id'] ?? null) === $studentId;
        });

        if (! $studentData) {
            abort(404, 'Result not found for the selected student in this term.');
        }

        $pdf = Pdf::loadView('results.result-sheet', ['data' => $studentData])
            ->setPaper('a4', 'portrait');

        $studentName = str_replace([' ', '/'], '_', trim($studentData['student']['name']));
        $termName    = str_replace([' ', '/'], '_', trim($studentData['term_name']));
        $filename    = "{$studentName}_{$termName}_result.pdf";

        if ($request->boolean('stream')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }
}
