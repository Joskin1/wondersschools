<?php

namespace App\Filament\Teacher\Pages;

use App\Models\ClassTermApproval;
use App\Models\Classroom;
use App\Models\ClassTeacherAssignment;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\ClassRemarksSpreadsheetService;
use App\Services\ResultCalculationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ProcessClassResults extends Page
{
    use WithFileUploads;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check-badge';

    protected static string | \UnitEnum | null $navigationGroup = 'Results';

    protected static ?string $navigationLabel = 'Process Class Results';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.teacher.pages.process-class-results';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->canAccessAdminPage('process_class_results')) {
            return true;
        }

        return ClassTeacherAssignment::where('teacher_id', $user->id)->exists();
    }

    public ?int $session_id = null;
    public ?int $term_id = null;
    public ?int $classroom_id = null;

    // ── Student Remarks & Attendance state ───────────────────────────────────
    /** @var array<int, array{times_present: int|string|null, class_teacher_comment: string}> */
    public array $remarks = [];

    // ── Spreadsheet file upload ──────────────────────────────────────────────
    public $uploadFile = null;

    // ── Admin Final Approval state ───────────────────────────────────────────
    public ?int $times_school_opened = null;
    public ?string $next_term_begins = null;
    public string $approvalStatus = 'pending';
    public ?string $approvedAt = null;
    public ?string $approvedByName = null;

    public function mount(): void
    {
        $activeSession = Session::where('is_active', true)->first();
        if ($activeSession) {
            $this->session_id = $activeSession->id;
            $activeTerm = Term::where('session_id', $activeSession->id)
                ->where('is_active', true)
                ->first();
            if ($activeTerm) {
                $this->term_id = $activeTerm->id;
            }
        }

        $user = Auth::user();
        if ($user && $user->isTeacher()) {
            $assignedClassId = ClassTeacherAssignment::where('teacher_id', $user->id)
                ->where('session_id', $this->session_id)
                ->value('class_id');
            if ($assignedClassId) {
                $this->classroom_id = $assignedClassId;
            }
        }

        if (request()->has('session_id')) {
            $this->session_id = (int) request()->query('session_id');
        }
        if (request()->has('term_id')) {
            $this->term_id = (int) request()->query('term_id');
        }
        if (request()->has('classroom_id')) {
            $this->classroom_id = (int) request()->query('classroom_id');
        }

        $this->loadClassData();
    }

    public function updatedSessionId(): void
    {
        $this->term_id = null;
        $this->classroom_id = null;
        $this->loadClassData();
    }

    public function updatedTermId(): void
    {
        $this->classroom_id = null;
        $this->loadClassData();
    }

    public function updatedClassroomId(): void
    {
        $this->loadClassData();
    }

    public function loadClassData(): void
    {
        $this->remarks = [];

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            $this->times_school_opened = null;
            $this->next_term_begins = null;
            $this->approvalStatus = 'pending';
            $this->approvedAt = null;
            $this->approvedByName = null;
            return;
        }

        // Load existing term results for student remarks
        $termResults = TermResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->get()
            ->keyBy('student_id');

        $enrolledIds = StudentEnrollment::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->pluck('student_id');

        foreach ($enrolledIds as $studentId) {
            $tr = $termResults->get($studentId);
            $this->remarks[$studentId] = [
                'times_present'         => $tr?->times_present,
                'class_teacher_comment' => $tr?->class_teacher_comment ?? '',
                'principal_comment'     => $tr?->principal_comment ?? '',
            ];
        }

        // Load approval record
        $approval = ClassTermApproval::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->with('approvedBy')
            ->first();

        if ($approval) {
            $this->times_school_opened = $approval->times_school_opened;
            $this->next_term_begins    = $approval->next_term_begins;
            $this->approvalStatus      = $approval->status;
            $this->approvedAt          = $approval->approved_at?->format('d M Y, h:i A');
            $this->approvedByName      = $approval->approvedBy?->name;
        } else {
            $this->times_school_opened = null;
            $this->next_term_begins    = null;
            $this->approvalStatus      = 'pending';
            $this->approvedAt          = null;
            $this->approvedByName      = null;
        }
    }

    public function getSessionsProperty()
    {
        return Session::orderByDesc('start_year')->get();
    }

    public function getTermsProperty()
    {
        if (! $this->session_id) {
            return collect();
        }

        return Term::where('session_id', $this->session_id)
            ->orderBy('order')
            ->get();
    }

    public function getClassroomsProperty()
    {
        $user = Auth::user();
        if ($user->canManageAcademics()) {
            return Classroom::orderBy('name')->get();
        }

        $assignedIds = ClassTeacherAssignment::where('teacher_id', $user->id)
            ->where('session_id', $this->session_id)
            ->pluck('class_id');

        return Classroom::whereIn('id', $assignedIds)->orderBy('name')->get();
    }

    public function getSubjectStatusListProperty(): array
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return [];
        }

        $classroom = Classroom::with('subjects')->find($this->classroom_id);
        if (! $classroom) {
            return [];
        }

        $classroomSubjectIds = $classroom->subjects->pluck('id')->toArray();
        $assignedSubjectIds = TeacherSubjectAssignment::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->pluck('subject_id')
            ->toArray();
        $resultSubjectIds = SubjectResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->pluck('subject_id')
            ->toArray();

        $allSubjectIds = array_unique(array_merge($classroomSubjectIds, $assignedSubjectIds, $resultSubjectIds));
        $subjects = Subject::whereIn('id', $allSubjectIds)->active()->orderBy('name')->get();

        $publishedSubjectIds = SubjectResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('is_published', true)
            ->distinct()
            ->pluck('subject_id')
            ->toArray();

        $list = [];
        foreach ($subjects as $subject) {
            $isPublished = in_array($subject->id, $publishedSubjectIds);
            $list[] = [
                'id'           => $subject->id,
                'name'         => $subject->name,
                'code'         => $subject->code ?? '',
                'is_published' => $isPublished,
            ];
        }

        return $list;
    }

    public function getStudentsListProperty(): array
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return [];
        }

        $user = Auth::user();
        $assignment = null;
        if ($user && ! $user->canManageAcademics()) {
            $assignment = ClassTeacherAssignment::where('teacher_id', $user->id)
                ->where('class_id', $this->classroom_id)
                ->where('session_id', $this->session_id)
                ->first();
        }

        $query = StudentEnrollment::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->with(['student', 'department', 'classArm']);

        if ($assignment?->department_id) {
            $query->where('department_id', $assignment->department_id);
        } elseif ($assignment?->class_arm_id) {
            $query->where('class_arm_id', $assignment->class_arm_id);
        }

        $enrollments = $query->get()->sortBy(fn ($e) => $e->student?->full_name ?? '');

        $termResults = TermResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->get()
            ->keyBy('student_id');

        $list = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }

            $tr = $termResults->get($student->id);

            $list[] = [
                'student_id'            => $student->id,
                'admission_number'      => $student->admission_number ?? '—',
                'full_name'             => $student->full_name,
                'department'            => $enrollment->department?->name ?? null,
                'arm'                   => $enrollment->classArm?->name ?? null,
                'average'               => $tr ? (float) $tr->average : null,
                'position'              => $tr?->overall_position,
                'class_population'      => $tr?->class_population,
                'department_position'   => $tr?->department_position,
                'department_population' => $tr?->department_population,
                'arm_position'          => $tr?->arm_position,
                'arm_population'        => $tr?->arm_population,
                'grade'                 => $tr?->grade,
                'grand_total'           => $tr ? (float) $tr->grand_total : null,
                'principal_comment'     => $tr?->principal_comment ?? null,
                'is_finalized'          => (bool) ($tr?->is_finalized ?? false),
            ];
        }

        return $list;
    }

    public function getIsClassFinalizedProperty(): bool
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return false;
        }

        return TermResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('is_finalized', true)
            ->exists();
    }

    public function getCanApproveProperty(): bool
    {
        $user = Auth::user();
        return $user && $user->canManageAcademics();
    }

    public function processClassResults(): void
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and classroom.')->warning()->send();
            return;
        }

        try {
            app(ResultCalculationService::class)->calculateForClass(
                $this->classroom_id,
                $this->session_id,
                $this->term_id
            );

            $this->loadClassData();

            Notification::make()
                ->title('Class results processed and published successfully!')
                ->body('Scores calculated. You can now enter student remarks & attendance.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Processing Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function saveRemarks(): void
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and classroom.')->warning()->send();
            return;
        }

        foreach ($this->remarks as $studentId => $data) {
            $timesPresent = isset($data['times_present']) && $data['times_present'] !== ''
                ? (int) $data['times_present']
                : null;
            $comment = isset($data['class_teacher_comment']) && trim($data['class_teacher_comment']) !== ''
                ? trim($data['class_teacher_comment'])
                : null;
            $principalComment = isset($data['principal_comment']) && trim($data['principal_comment']) !== ''
                ? trim($data['principal_comment'])
                : null;

            $termResult = TermResult::where('student_id', $studentId)
                ->where('session_id', $this->session_id)
                ->where('term_id', $this->term_id)
                ->first();

            if (! $termResult) {
                $termResult = new TermResult([
                    'student_id'   => $studentId,
                    'classroom_id' => $this->classroom_id,
                    'session_id'   => $this->session_id,
                    'term_id'      => $this->term_id,
                    'is_finalized' => false,
                ]);
            }

            $termResult->times_present = $timesPresent;
            $termResult->class_teacher_comment = $comment;
            if ($this->canApprove && $principalComment !== null) {
                $termResult->principal_comment = $principalComment;
            } elseif (! $termResult->principal_comment && $termResult->average !== null) {
                $grading = app(ResultCalculationService::class)->resolveGrade((float) $termResult->average);
                $termResult->principal_comment = $grading['principal_comment'] ?? null;
            }
            $termResult->save();
        }

        Notification::make()
            ->title('Student remarks & attendance saved successfully!')
            ->success()
            ->send();
    }

    public function exportRemarksSpreadsheet()
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and classroom first.')->warning()->send();
            return null;
        }

        try {
            $service = app(ClassRemarksSpreadsheetService::class);
            $tempPath = $service->exportToTempFile(
                $this->classroom_id,
                $this->session_id,
                $this->term_id
            );

            $classroom = Classroom::find($this->classroom_id);
            $session   = Session::find($this->session_id);
            $term      = Term::find($this->term_id);

            $cleanClass = preg_replace('/[^A-Za-z0-9_\-]/', '_', $classroom?->name ?? 'Class');
            $cleanTerm  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $term?->name ?? 'Term');
            $cleanSess  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $session?->name ?? 'Session');

            $fileName = "Remarks_Attendance_{$cleanClass}_{$cleanTerm}_{$cleanSess}.xlsx";

            return response()->download($tempPath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Notification::make()->title('Export Failed')->body($e->getMessage())->danger()->send();
            return null;
        }
    }

    public function updatedUploadFile(): void
    {
        if (! $this->uploadFile) {
            return;
        }

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and classroom first.')->warning()->send();
            $this->uploadFile = null;
            return;
        }

        try {
            $service = app(ClassRemarksSpreadsheetService::class);
            $result = $service->importSpreadsheet(
                $this->uploadFile->getRealPath(),
                $this->classroom_id,
                $this->session_id,
                $this->term_id
            );

            $this->loadClassData();
            $this->uploadFile = null;

            if (! empty($result['errors'])) {
                Notification::make()
                    ->title("Import Completed with {$result['updated']} updated students")
                    ->body(implode(' ', array_slice($result['errors'], 0, 3)))
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title("Successfully imported remarks & attendance for {$result['updated']} students!")
                    ->success()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->uploadFile = null;
            Notification::make()->title('Import Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function approveClassResults(): void
    {
        if (! $this->canApprove) {
            Notification::make()->title('Unauthorized action. Admin privileges required.')->danger()->send();
            return;
        }

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and classroom first.')->warning()->send();
            return;
        }

        $this->validate([
            'times_school_opened' => 'required|integer|min:1',
            'next_term_begins'    => 'required|string|max:100',
        ], [
            'times_school_opened.required' => 'Please enter the number of times school opened for this class.',
            'next_term_begins.required'    => 'Please enter the date or description when next term begins.',
        ]);

        ClassTermApproval::updateOrCreate(
            [
                'classroom_id' => $this->classroom_id,
                'session_id'   => $this->session_id,
                'term_id'      => $this->term_id,
            ],
            [
                'times_school_opened' => $this->times_school_opened,
                'next_term_begins'    => $this->next_term_begins,
                'status'              => 'approved',
                'approved_by'         => Auth::id(),
                'approved_at'         => now(),
            ]
        );

        // Ensure term results are marked as finalized
        TermResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->update(['is_finalized' => true]);

        $this->loadClassData();

        Notification::make()
            ->title('Class results approved and released to students!')
            ->body('Students and parents can now view their report cards on the portal.')
            ->success()
            ->send();
    }

    public function unapproveClassResults(): void
    {
        if (! $this->canApprove) {
            Notification::make()->title('Unauthorized action. Admin privileges required.')->danger()->send();
            return;
        }

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return;
        }

        $approval = ClassTermApproval::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->first();

        if ($approval) {
            $approval->update([
                'status'      => 'pending',
                'approved_by' => null,
                'approved_at' => null,
            ]);
        }

        $this->loadClassData();

        Notification::make()
            ->title('Class result approval reverted.')
            ->body('Results are now hidden from students until re-approved.')
            ->warning()
            ->send();
    }
}
