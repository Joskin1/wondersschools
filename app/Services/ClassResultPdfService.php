<?php

namespace App\Services;

use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\ClassTermApproval;
use App\Models\Classroom;
use App\Models\MidtermClassTermApproval;
use App\Models\MidtermSubjectResult;
use App\Models\MidtermTermResult;
use App\Models\Score;
use App\Models\Session;
use App\Models\Setting;
use App\Models\StudentEnrollment;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermScoringSnapshot;
use Illuminate\Support\Facades\Storage;

class ClassResultPdfService
{
    /**
     * Resolve image path to base64 data-URI for DomPDF.
     */
    public static function resolveImageBase64(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (str_starts_with($path, 'data:')) {
            return $path;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                $fullPath = Storage::disk('public')->path($path);
                if (file_exists($fullPath)) {
                    $mime = mime_content_type($fullPath) ?: 'image/png';
                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }
        } catch (\Throwable $e) {}

        $pubPath = public_path('storage/' . $path);
        if (file_exists($pubPath)) {
            $mime = mime_content_type($pubPath) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($pubPath));
        }

        $stPath = storage_path('app/public/' . $path);
        if (file_exists($stPath)) {
            $mime = mime_content_type($stPath) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($stPath));
        }

        return null;
    }

    /**
     * Build batch dataset for all students in a classroom for Terminal Results.
     */
    public function buildTerminalClassData(
        int $classroomId,
        int $sessionId,
        int $termId,
        ?int $classArmId = null,
        ?int $departmentId = null
    ): array {
        $classroom = Classroom::findOrFail($classroomId);
        $session   = Session::findOrFail($sessionId);
        $term      = Term::where('id', $termId)->where('session_id', $sessionId)->firstOrFail();

        $approval = ClassTermApproval::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        $isCumulative = ($term->order === 3);

        // Scoring snapshot / structure
        $snapshot = TermScoringSnapshot::where('classroom_id', $classroomId)
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
            foreach ($snapshot->exam_heads ?? [] as $eh) {
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

        // Enrollments
        $enrollmentsQuery = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->with(['student.user', 'classroom', 'department', 'classArm']);

        if ($classArmId) {
            $enrollmentsQuery->where('class_arm_id', $classArmId);
        }
        if ($departmentId) {
            $enrollmentsQuery->where('department_id', $departmentId);
        }

        $enrollments = $enrollmentsQuery->get()->sortBy(fn ($e) => $e->student?->full_name ?? '');
        $studentIds  = $enrollments->pluck('student_id')->toArray();

        // Bulk load TermResults
        $termResults = TermResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        // Bulk load SubjectResults
        $allSubjectResults = SubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $studentIds)
            ->with('subject')
            ->get()
            ->groupBy('student_id');

        // Pre-fetch count of students examined per subject in this classroom/session/term
        $studentsExaminedCounts = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->selectRaw('subject_id, count(distinct student_id) as examined_count')
            ->groupBy('subject_id')
            ->pluck('examined_count', 'subject_id');

        // Bulk load scores grouped by student_id and subject_id
        $allScores = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy(fn ($item) => $item->student_id . '_' . $item->subject_id);

        // Cumulative prior results
        $priorResults = [];
        $termIds = null;
        if ($isCumulative) {
            $service = app(ResultCalculationService::class);
            $termIds = $service->resolveTermIds($sessionId);
            if ($termIds) {
                $priorRows = SubjectResult::whereIn('student_id', $studentIds)
                    ->where('session_id', $sessionId)
                    ->whereIn('term_id', [$termIds[1], $termIds[2]])
                    ->get();
                foreach ($priorRows as $pr) {
                    $priorResults[$pr->student_id][$pr->subject_id][$pr->term_id] = (float) $pr->total;
                }
            }
        }

        // Populations & Settings
        $classSize = StudentEnrollment::where('classroom_id', $classroomId)->where('session_id', $sessionId)->count();
        $armPopulations = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->whereNotNull('class_arm_id')
            ->selectRaw('class_arm_id, count(*) as count')
            ->groupBy('class_arm_id')
            ->pluck('count', 'class_arm_id');

        $deptPopulations = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) as count')
            ->groupBy('department_id')
            ->pluck('count', 'department_id');

        $rcDefaults      = ReportCardSettingsService::defaults();
        $storedSettings  = Setting::all()->pluck('value', 'key')->toArray();
        $settings        = array_merge($rcDefaults, $storedSettings);
        foreach ($rcDefaults as $k => $v) {
            if (is_bool($v) && isset($settings[$k])) {
                $settings[$k] = filter_var($settings[$k], FILTER_VALIDATE_BOOLEAN);
            }
        }
        $marginCss = ReportCardSettingsService::getMarginCss($settings);

        // Pre-resolve school logo once
        $schoolLogoPath = $settings['school_logo'] ?? null;
        $logoBase64 = self::resolveImageBase64($schoolLogoPath);
        $settings['school_logo_data_uri'] = $logoBase64;

        $studentsData = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }

            $termResult = $termResults->get($student->id);
            if (! $termResult) {
                continue;
            }

            $studentSubjectResults = $allSubjectResults->get($student->id, collect());
            $subjectRows = [];

            if ($isCumulative) {
                foreach ($studentSubjectResults as $sr) {
                    $subjectId = $sr->subject_id;
                    $t1 = $priorResults[$student->id][$subjectId][$termIds[1] ?? 0] ?? 0;
                    $t2 = $priorResults[$student->id][$subjectId][$termIds[2] ?? 0] ?? 0;

                    $scores = [];
                    $t3Raw = 0;
                    $subjectScores = $allScores->get($student->id . '_' . $subjectId, collect());

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
                foreach ($studentSubjectResults as $sr) {
                    $scores = [];
                    $subjectScores = $allScores->get($student->id . '_' . $sr->subject_id, collect());

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

            $armPopulation = $termResult->arm_population ?? ($enrollment->class_arm_id ? ($armPopulations[$enrollment->class_arm_id] ?? null) : null);
            $departmentPopulation = $termResult->department_population ?? ($enrollment->department_id ? ($deptPopulations[$enrollment->department_id] ?? null) : null);

            $totalObtainable = ($isCumulative || $isMidterm)
                ? 100 * count($subjectRows)
                : ($structure?->total_score ?? 100) * count($subjectRows);

            $studentPhotoBase64 = self::resolveImageBase64($student->profile_picture);

            $studentsData[] = [
                'student'      => [
                    'id'             => $student->id,
                    'name'           => $student->name,
                    'gender'         => $student->gender ?? '-',
                    'dob'            => $student->date_of_birth?->format('d/m/Y') ?? '-',
                    'admission_no'   => $student->admission_number ?? '-',
                    'department'     => $enrollment->department?->name ?? null,
                    'arm'            => $enrollment->classArm?->name ?? null,
                    'passport_photo' => $studentPhotoBase64,
                ],
                'classroom'             => $enrollment->classroom?->name ?? $classroom->name,
                'class_size'            => $classSize,
                'arm_population'        => $armPopulation,
                'department_population' => $departmentPopulation,
                'session_name'          => $session->name ?? '-',
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
        }

        return [
            'classroom'    => $classroom,
            'session'      => $session,
            'term'         => $term,
            'settings'     => $settings,
            'margin_css'   => $marginCss,
            'studentsData' => $studentsData,
        ];
    }

    /**
     * Build batch dataset for all students in a classroom for Mid-Term Results.
     */
    public function buildMidtermClassData(
        int $classroomId,
        int $sessionId,
        int $termId,
        ?int $classArmId = null,
        ?int $departmentId = null
    ): array {
        $classroom = Classroom::findOrFail($classroomId);
        $session   = Session::findOrFail($sessionId);
        $term      = Term::where('id', $termId)->where('session_id', $sessionId)->firstOrFail();

        $snapshot = TermScoringSnapshot::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->firstOrFail();

        if ($snapshot->mode !== 'midterm') {
            throw new \RuntimeException('Midterm scoring mode is not active for this class and term.');
        }

        $approval = MidtermClassTermApproval::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        // Enrollments
        $enrollmentsQuery = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->with(['student.user', 'classroom', 'department', 'classArm']);

        if ($classArmId) {
            $enrollmentsQuery->where('class_arm_id', $classArmId);
        }
        if ($departmentId) {
            $enrollmentsQuery->where('department_id', $departmentId);
        }

        $enrollments = $enrollmentsQuery->get()->sortBy(fn ($e) => $e->student?->full_name ?? '');
        $studentIds  = $enrollments->pluck('student_id')->toArray();

        // Bulk load MidtermTermResults
        $termResults = MidtermTermResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        // Bulk load MidtermSubjectResults
        $allSubjectResults = MidtermSubjectResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $studentIds)
            ->with('subject')
            ->get()
            ->groupBy('student_id');

        // Midterm score heads from snapshot
        $midtermHeads = $snapshot->midterm_heads ?? [];
        $midtermHeadIds = collect($midtermHeads)->pluck('id')->toArray();

        // Bulk load scores
        $allScores = Score::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $studentIds)
            ->whereIn('score_head_id', $midtermHeadIds)
            ->get()
            ->groupBy(fn ($item) => $item->student_id . '_' . $item->subject_id);

        $classSize = StudentEnrollment::where('classroom_id', $classroomId)->where('session_id', $sessionId)->count();
        $armPopulations = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->whereNotNull('class_arm_id')
            ->selectRaw('class_arm_id, count(*) as count')
            ->groupBy('class_arm_id')
            ->pluck('count', 'class_arm_id');

        $deptPopulations = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) as count')
            ->groupBy('department_id')
            ->pluck('count', 'department_id');

        $storedSettings = Setting::all()->pluck('value', 'key')->toArray();
        $schoolLogoPath = $storedSettings['school_logo'] ?? null;
        $logoBase64 = self::resolveImageBase64($schoolLogoPath);
        $storedSettings['school_logo_data_uri'] = $logoBase64;

        $reportTitle = $storedSettings['midterm_report_title'] ?? 'Mid-Term Progress Report';

        $studentsData = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }

            $termResult = $termResults->get($student->id);
            if (! $termResult) {
                continue;
            }

            $studentSubjectResults = $allSubjectResults->get($student->id, collect());
            $subjectRows = [];

            foreach ($studentSubjectResults as $sr) {
                $headScores = [];
                $subjectScores = $allScores->get($student->id . '_' . $sr->subject_id, collect());

                foreach ($midtermHeads as $mh) {
                    $s = $subjectScores->firstWhere('score_head_id', $mh['id']);
                    if (! $s) {
                        $headScores[$mh['id']] = '—';
                    } elseif ($s->is_absent) {
                        $headScores[$mh['id']] = 'ABS';
                    } else {
                        $headScores[$mh['id']] = (float) $s->score;
                    }
                }

                $isAbsent = ($sr->status === 'absent');
                $isMissing = ($sr->status === 'missing');

                $subjectRows[] = [
                    'subject'         => $sr->subject->name ?? 'Unknown',
                    'head_scores'     => $headScores,
                    'raw_score'       => ($isAbsent || $isMissing) ? '—' : (float) $sr->raw_score,
                    'student_raw_max' => $sr->student_raw_max,
                    'scaled_score'    => $isAbsent ? 'ABS' : ($isMissing ? '—' : (float) $sr->scaled_score),
                    'percentage'      => $isAbsent ? 'ABS' : ($isMissing ? '—' : (float) $sr->percentage),
                    'grade'           => $sr->grade,
                    'remark'          => $sr->remark,
                ];
            }

            $armPopulation = $termResult->arm_population ?? ($enrollment->class_arm_id ? ($armPopulations[$enrollment->class_arm_id] ?? null) : null);
            $departmentPopulation = $termResult->department_population ?? ($enrollment->department_id ? ($deptPopulations[$enrollment->department_id] ?? null) : null);

            $studentPhotoBase64 = self::resolveImageBase64($student->profile_picture);

            $studentsData[] = [
                'report_title'          => strtoupper($term->name) . ' MID-TERM ACADEMIC PROGRESS REPORT',
                'settings'              => $storedSettings,
                'session_name'          => $session->name ?? '-',
                'term_name'             => $term->name ?? '-',
                'classroom'             => $enrollment->classroom?->name ?? $classroom->name,
                'class_size'            => $classSize,
                'arm_population'        => $armPopulation,
                'department_population' => $departmentPopulation,
                'midterm_weight'        => $snapshot->midterm_weight,
                'raw_max'               => $snapshot->raw_max,
                'midterm_heads'         => $snapshot->midterm_heads,
                'student'               => [
                    'id'              => $student->id,
                    'name'            => $student->name,
                    'admission_no'    => $student->admission_number ?? '—',
                    'gender'          => $student->gender ?? '—',
                    'dob'             => $student->date_of_birth?->format('d/m/Y') ?? '—',
                    'arm'             => $enrollment->classArm?->name ?? null,
                    'department'      => $enrollment->department?->name ?? null,
                    'passport_photo'  => $studentPhotoBase64,
                ],
                'snapshot'              => $snapshot,
                'subjects'              => $subjectRows,
                'term_result'           => [
                    'subjects_count'        => $termResult->subjects_count,
                    'total_obtainable'      => (float) $termResult->total_obtainable,
                    'total_obtained'        => (float) $termResult->total_obtained,
                    'percentage'            => (float) $termResult->percentage,
                    'grade'                 => $termResult->grade,
                    'remark'                => $termResult->remark,
                    'class_population'      => $classSize,
                    'arm_population'        => $armPopulation,
                    'department_population' => $departmentPopulation,
                    'teacher_comment'       => $termResult->teacher_comment,
                    'head_teacher_comment'  => $termResult->head_teacher_comment,
                ],
            ];
        }

        return [
            'classroom'    => $classroom,
            'session'      => $session,
            'term'         => $term,
            'settings'     => $storedSettings,
            'studentsData' => $studentsData,
        ];
    }
}
