<?php

namespace App\Filament\Teacher\Pages;

use App\Models\ClassMidtermConfig;
use App\Models\Classroom;
use App\Models\ClassTeacherAssignment;
use App\Models\MidtermClassTermApproval;
use App\Models\MidtermSubjectResult;
use App\Models\MidtermTermResult;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Score;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermScoringSnapshot;
use App\Services\MidtermCalculationService;
use App\Services\ResultCalculationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ProcessMidtermResults extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check-badge';

    protected static string | \UnitEnum | null $navigationGroup = 'Results';

    protected static ?string $navigationLabel = 'Process Mid-Term Results';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.teacher.pages.process-midterm-results';

    public static function canAccess(): bool
    {
        $useMidterm = filter_var(Setting::where('key', 'use_midterm_results')->value('value'), FILTER_VALIDATE_BOOLEAN);
        if (! $useMidterm) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->canAccessAdminPage('process_class_results') || $user->canAccessAdminPage('process_midterm_results')) {
            return true;
        }

        return ClassTeacherAssignment::where('teacher_id', $user->id)->exists();
    }

    public ?int $session_id = null;
    public ?int $term_id = null;
    public ?int $classroom_id = null;

    /** @var array<int, array{teacher_comment: string, head_teacher_comment: string}> */
    public array $comments = [];

    public bool $showUnscoredLockWarning = false;
    public string $unscoredClassSubjectsText = '';

    // Approval / Lock state
    public bool $isLocked = false;
    public ?string $lockedAt = null;
    public ?string $lockedByName = null;
    public bool $isPublished = false;
    public ?string $publishedAt = null;
    public ?string $publishedByName = null;

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
        if ($user && $user->isTeacher() && ! $user->canManageAcademics()) {
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
        $this->comments = [];

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            $this->isLocked = false;
            $this->lockedAt = null;
            $this->lockedByName = null;
            $this->isPublished = false;
            $this->publishedAt = null;
            $this->publishedByName = null;
            return;
        }

        $approval = MidtermClassTermApproval::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->with(['lockedBy', 'publishedBy'])
            ->first();

        if ($approval) {
            $this->isLocked = (bool) $approval->is_locked;
            $this->lockedAt = $approval->locked_at?->format('d M Y, h:i A');
            $this->lockedByName = $approval->lockedBy?->name;
            $this->isPublished = (bool) $approval->is_published;
            $this->publishedAt = $approval->published_at?->format('d M Y, h:i A');
            $this->publishedByName = $approval->publishedBy?->name;
        } else {
            $this->isLocked = false;
            $this->lockedAt = null;
            $this->lockedByName = null;
            $this->isPublished = false;
            $this->publishedAt = null;
            $this->publishedByName = null;
        }

        $termResults = MidtermTermResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->get()
            ->keyBy('student_id');

        $enrolledIds = StudentEnrollment::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->pluck('student_id');

        foreach ($enrolledIds as $studentId) {
            $tr = $termResults->get($studentId);
            $this->comments[$studentId] = [
                'teacher_comment'      => $tr?->teacher_comment ?? '',
                'head_teacher_comment' => $tr?->head_teacher_comment ?? '',
            ];
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
        if ($user && $user->canManageAcademics()) {
            return Classroom::orderBy('name')->get();
        }

        $assignedIds = ClassTeacherAssignment::where('teacher_id', $user?->id)
            ->where('session_id', $this->session_id)
            ->pluck('class_id');

        return Classroom::whereIn('id', $assignedIds)->orderBy('name')->get();
    }

    public function getCanApproveProperty(): bool
    {
        $user = Auth::user();
        return (bool) ($user && $user->canManageAcademics());
    }

    public function getSnapshotProperty(): ?TermScoringSnapshot
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return null;
        }

        return TermScoringSnapshot::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->first();
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
            ->with(['student.user', 'department', 'classArm']);

        if ($assignment?->department_id) {
            $query->where('department_id', $assignment->department_id);
        } elseif ($assignment?->class_arm_id) {
            $query->where('class_arm_id', $assignment->class_arm_id);
        }

        $enrollments = $query->get()->sortBy(fn ($e) => $e->student?->full_name ?? '');

        $termResults = MidtermTermResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->get()
            ->keyBy('student_id');

        $subjectResults = MidtermSubjectResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->get()
            ->groupBy('student_id');

        $list = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }

            $tr = $termResults->get($student->id);
            $sResults = $subjectResults->get($student->id, collect());

            $missingCount = $sResults->where('status', 'missing')->count();
            $absentCount = $sResults->where('status', 'absent')->count();
            $completeCount = $sResults->where('status', 'complete')->count();

            $list[] = [
                'student_id'       => $student->id,
                'admission_number' => $student->admission_number ?? '—',
                'full_name'        => $student->full_name,
                'department'       => $enrollment->department?->name ?? null,
                'arm'              => $enrollment->classArm?->name ?? null,
                'subjects_count'   => $tr?->subjects_count ?? $sResults->count(),
                'total_obtainable' => $tr ? (float) $tr->total_obtainable : null,
                'total_obtained'   => $tr ? (float) $tr->total_obtained : null,
                'percentage'       => $tr ? (float) $tr->percentage : null,
                'grade'            => $tr?->grade ?? '—',
                'remark'           => $tr?->remark ?? '—',
                'missing_count'    => $missingCount,
                'absent_count'     => $absentCount,
                'complete_count'   => $completeCount,
                'has_missing'      => $missingCount > 0,
            ];
        }

        return $list;
    }

    public function calculateMidtermResults(): void
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and classroom.')->warning()->send();
            return;
        }

        if ($this->isLocked) {
            Notification::make()
                ->title('Class Mid-Term is Locked')
                ->body('Cannot recalculate scores while results are locked. Admin must unlock first.')
                ->danger()
                ->send();
            return;
        }

        try {
            $calcService = app(MidtermCalculationService::class);
            $snapshot = $this->snapshot;

            if (! $snapshot) {
                $snapshot = $calcService->getOrCreateSnapshot($this->classroom_id, $this->session_id, $this->term_id, Auth::id());
            }

            if ($snapshot->mode !== 'midterm') {
                Notification::make()
                    ->title('Class is not in Mid-Term mode')
                    ->body('This class does not have an active mid-term configuration.')
                    ->warning()
                    ->send();
                return;
            }

            $classroom = Classroom::with('subjects')->find($this->classroom_id);
            $subjectIds = $classroom ? $classroom->subjects->pluck('id')->toArray() : [];

            // Also include subjects that have scores entered
            $scoredSubjectIds = \App\Models\Score::where('classroom_id', $this->classroom_id)
                ->where('session_id', $this->session_id)
                ->where('term_id', $this->term_id)
                ->distinct()
                ->pluck('subject_id')
                ->toArray();

            $allSubjectIds = array_unique(array_merge($subjectIds, $scoredSubjectIds));

            $enrolledStudentIds = StudentEnrollment::where('classroom_id', $this->classroom_id)
                ->where('session_id', $this->session_id)
                ->pluck('student_id');

            $absentPolicy = (string) (Setting::where('key', 'midterm_absent_policy')->value('value') ?? 'count_as_zero');
            $decimalPlaces = (int) (Setting::where('key', 'result_decimal_places')->value('value') ?? 2);
            $roundingRule = (string) (Setting::where('key', 'result_rounding_rule')->value('value') ?? 'half_up');

            foreach ($enrolledStudentIds as $studentId) {
                foreach ($allSubjectIds as $subjectId) {
                    $calcService->computeStudentSubjectMidterm(
                        $studentId,
                        $subjectId,
                        $this->classroom_id,
                        $this->session_id,
                        $this->term_id,
                        $snapshot,
                        $absentPolicy,
                        $decimalPlaces,
                        $roundingRule
                    );
                }
            }

            $calcService->computeClassMidtermSummary(
                $this->classroom_id,
                $this->session_id,
                $this->term_id,
                $snapshot,
                $decimalPlaces,
                $roundingRule
            );

            $this->loadClassData();

            Notification::make()
                ->title('Mid-term results calculated successfully!')
                ->body('Individual subject scaled scores and class summaries have been updated.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Calculation Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function saveComments(): void
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and classroom.')->warning()->send();
            return;
        }

        if ($this->isLocked) {
            Notification::make()->title('Cannot edit comments while results are locked.')->danger()->send();
            return;
        }

        foreach ($this->comments as $studentId => $data) {
            $teacherComment = isset($data['teacher_comment']) && trim($data['teacher_comment']) !== ''
                ? trim($data['teacher_comment'])
                : null;
            $headTeacherComment = isset($data['head_teacher_comment']) && trim($data['head_teacher_comment']) !== ''
                ? trim($data['head_teacher_comment'])
                : null;

            $termResult = MidtermTermResult::where('student_id', $studentId)
                ->where('session_id', $this->session_id)
                ->where('term_id', $this->term_id)
                ->first();

            if (! $termResult) {
                $termResult = new MidtermTermResult([
                    'student_id'   => $studentId,
                    'classroom_id' => $this->classroom_id,
                    'session_id'   => $this->session_id,
                    'term_id'      => $this->term_id,
                ]);
            }

            $termResult->teacher_comment = $teacherComment;
            if ($this->canApprove) {
                $termResult->head_teacher_comment = $headTeacherComment;
            }

            $termResult->save();
        }

        Notification::make()
            ->title('Comments saved successfully!')
            ->success()
            ->send();
    }

    public function lockMidtermResults(bool $forceConfirm = false): void
    {
        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and classroom.')->warning()->send();
            return;
        }

        // Completeness check: check if any student has missing scores
        $hasMissing = MidtermSubjectResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('status', 'missing')
            ->exists();

        if ($hasMissing) {
            Notification::make()
                ->title('Cannot Lock Results')
                ->body('One or more students have incomplete or missing mid-term scores. Ensure all scores are entered or marked absent before locking.')
                ->danger()
                ->send();
            return;
        }

        // Lock warning for class subjects with no scores for ANY student, requiring admin confirmation
        $classroom = Classroom::find($this->classroom_id);
        if ($classroom && ! $forceConfirm) {
            $classSubjectIds = $classroom->subjects()->pluck('subjects.id');
            $scoredSubjectIds = Score::where('classroom_id', $this->classroom_id)
                ->where('session_id', $this->session_id)
                ->where('term_id', $this->term_id)
                ->distinct()
                ->pluck('subject_id');

            $unscoredSubjectIds = $classSubjectIds->diff($scoredSubjectIds);
            if ($unscoredSubjectIds->isNotEmpty()) {
                $unscoredNames = Subject::whereIn('id', $unscoredSubjectIds)->pluck('name')->implode(', ');
                $this->unscoredClassSubjectsText = $unscoredNames;
                $this->showUnscoredLockWarning = true;

                Notification::make()
                    ->title('Warning: Unscored Subjects Detected')
                    ->body("The following class subject(s) have no scores for any student: {$unscoredNames}. Please confirm to proceed with locking.")
                    ->warning()
                    ->send();
                return;
            }
        }

        $this->showUnscoredLockWarning = false;

        MidtermClassTermApproval::updateOrCreate(
            [
                'classroom_id' => $this->classroom_id,
                'session_id'   => $this->session_id,
                'term_id'      => $this->term_id,
            ],
            [
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => Auth::id(),
            ]
        );

        $this->loadClassData();

        Notification::make()
            ->title('Mid-Term Results Locked')
            ->body('Scores are now locked against further edits.')
            ->success()
            ->send();
    }

    public function confirmLockWithUnscored(): void
    {
        $this->lockMidtermResults(true);
    }

    public function cancelLockWithUnscored(): void
    {
        $this->showUnscoredLockWarning = false;
    }

    public function unlockMidtermResults(): void
    {
        if (! $this->canApprove) {
            Notification::make()->title('Unauthorized. Only Admins can unlock results.')->danger()->send();
            return;
        }

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return;
        }

        $approval = MidtermClassTermApproval::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->first();

        if ($approval) {
            $approval->update([
                'is_locked'    => false,
                'locked_at'    => null,
                'locked_by'    => null,
                'is_published' => false,
                'published_at' => null,
                'published_by' => null,
            ]);
        }

        // Resync terminal results if they exist for this class & term
        $hasTerminal = SubjectResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->whereNotNull('midterm_score')
            ->exists();

        if ($hasTerminal) {
            try {
                app(ResultCalculationService::class)->calculateForClass(
                    $this->classroom_id,
                    $this->session_id,
                    $this->term_id
                );
            } catch (\Throwable $e) {
                // Silently continue or notify
            }
        }

        $this->loadClassData();

        Notification::make()
            ->title('Mid-Term Results Unlocked')
            ->body('Scores can now be modified. Any terminal results will be re-synced.')
            ->warning()
            ->send();
    }

    public function publishMidtermResults(): void
    {
        if (! $this->canApprove) {
            Notification::make()->title('Unauthorized. Only Admins can publish mid-term reports.')->danger()->send();
            return;
        }

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return;
        }

        // Completeness check
        $hasMissing = MidtermSubjectResult::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('status', 'missing')
            ->exists();

        if ($hasMissing) {
            Notification::make()
                ->title('Cannot Publish Mid-Term Reports')
                ->body('One or more students have incomplete scores. All scores must be entered or marked absent prior to release.')
                ->danger()
                ->send();
            return;
        }

        MidtermClassTermApproval::updateOrCreate(
            [
                'classroom_id' => $this->classroom_id,
                'session_id'   => $this->session_id,
                'term_id'      => $this->term_id,
            ],
            [
                'is_locked'    => true,
                'locked_at'    => now(),
                'locked_by'    => Auth::id(),
                'is_published' => true,
                'published_at' => now(),
                'published_by' => Auth::id(),
            ]
        );

        $this->loadClassData();

        Notification::make()
            ->title('Mid-Term Reports Published!')
            ->body('Mid-term progress reports are now accessible to students on the portal.')
            ->success()
            ->send();
    }

    public function unpublishMidtermResults(): void
    {
        if (! $this->canApprove) {
            Notification::make()->title('Unauthorized.')->danger()->send();
            return;
        }

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return;
        }

        $approval = MidtermClassTermApproval::where('classroom_id', $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->first();

        if ($approval) {
            $approval->update([
                'is_published' => false,
                'published_at' => null,
                'published_by' => null,
            ]);
        }

        $this->loadClassData();

        Notification::make()
            ->title('Mid-Term Reports Unpublished')
            ->body('Reports are no longer visible on the student portal.')
            ->warning()
            ->send();
    }
}
