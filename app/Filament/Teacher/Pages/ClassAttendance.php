<?php

declare(strict_types=1);

namespace App\Filament\Teacher\Pages;

use App\Models\Attendance;
use App\Models\ClassTeacherAssignment;
use App\Models\ClassWeeklyAttendance;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ClassAttendance extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $title = 'Class Attendance';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.teacher.pages.class-attendance';

    public ?int $session_id = null;
    public ?int $term_id = null;
    public ?int $classroom_id = null;
    public ?int $class_arm_id = null;
    public ?int $department_id = null;

    // Week and Date navigation
    public int $selected_week = 1;
    public string $selected_date = '';

    /**
     * Array of daily records for the current selected_date:
     * [ student_id => ['status' => 'present'|'absent'|'late'|'excused', 'remark' => string|null, 'arrival_time' => string|null] ]
     *
     * @var array<int, array{status: string, remark: ?string, arrival_time: ?string}>
     */
    public array $records = [];

    /**
     * Whether the teacher is permitted to edit the current selected date.
     */
    public bool $canEditCurrentDate = true;
    public ?string $editRestrictionReason = null;
    public bool $isCurrentWeekLocked = false;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->isSudo()) {
            return true;
        }

        if ($user->isTeacher()) {
            return ClassTeacherAssignment::where('teacher_id', $user->id)->exists();
        }

        return false;
    }

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
        if ($user && $user->isTeacher() && ! $user->isAdmin() && ! $user->isSudo()) {
            $assignment = ClassTeacherAssignment::where('teacher_id', $user->id)
                ->when($this->session_id, fn ($q) => $q->where('session_id', $this->session_id))
                ->first();

            if ($assignment) {
                $this->classroom_id = $assignment->class_id;
                $this->class_arm_id = $assignment->class_arm_id;
                $this->department_id = $assignment->department_id;
            }
        } else {
            // Admin default
            $firstClass = Classroom::where('is_active', true)->orderBy('class_order')->first();
            $this->classroom_id = $firstClass?->id;
        }

        // Determine current week and date
        $today = now();
        // If today is weekend (Saturday=6, Sunday=7), default to Friday
        if ($today->dayOfWeekIso > 5) {
            $this->selected_date = $today->copy()->previous(Carbon::FRIDAY)->toDateString();
        } else {
            $this->selected_date = $today->toDateString();
        }

        if ($this->term_id) {
            $term = Term::find($this->term_id);
            if ($term) {
                $weeks = app(AttendanceService::class)->getTermWeeks($term);
                foreach ($weeks as $wNum => $wData) {
                    if ($wData['is_current']) {
                        $this->selected_week = $wNum;
                        break;
                    }
                }
            }
        }

        $this->loadDayAttendance();
    }

    public function updatedSelectedWeek(): void
    {
        // When week changes, jump to Monday of that week
        $weeks = $this->weeks;
        if (isset($weeks[$this->selected_week])) {
            $this->selected_date = $weeks[$this->selected_week]['start_date'];
        }
        $this->loadDayAttendance();
    }

    public function updatedSelectedDate(): void
    {
        $d = Carbon::parse($this->selected_date);
        $weeks = $this->weeks;
        foreach ($weeks as $wNum => $wData) {
            $wStart = Carbon::parse($wData['start_date'])->startOfDay();
            $wEnd = Carbon::parse($wData['end_date'])->endOfDay();
            if ($d->betweenIncluded($wStart, $wEnd)) {
                $this->selected_week = $wNum;
                break;
            }
        }
        $this->loadDayAttendance();
    }

    public function selectDate(string $date): void
    {
        $this->selected_date = $date;
        $this->updatedSelectedDate();
    }

    public function updatedClassroomId(): void
    {
        $this->class_arm_id = null;
        $this->department_id = null;
        $this->loadDayAttendance();
    }

    public function updatedClassArmId(): void
    {
        $this->loadDayAttendance();
    }

    public function updatedDepartmentId(): void
    {
        $this->loadDayAttendance();
    }

    /**
     * Load attendance records for the selected date and evaluate edit permissions.
     */
    public function loadDayAttendance(): void
    {
        if (! $this->classroom_id || ! $this->term_id || ! $this->session_id || ! $this->selected_date) {
            $this->records = [];
            return;
        }

        $service = app(AttendanceService::class);
        $weeks = $this->weeks;
        $weekData = $weeks[$this->selected_week] ?? null;

        $startDate = $weekData ? Carbon::parse($weekData['start_date']) : Carbon::parse($this->selected_date)->startOfWeek();
        $endDate = $weekData ? Carbon::parse($weekData['end_date']) : Carbon::parse($this->selected_date)->endOfWeek();

        // 1. Get or create weekly container
        $weekly = $service->getOrCreateWeeklyAttendance(
            $this->session_id,
            $this->term_id,
            $this->classroom_id,
            $this->class_arm_id,
            $this->department_id,
            $this->selected_week,
            $startDate,
            $endDate
        );

        $this->isCurrentWeekLocked = $weekly->isLocked();

        // 2. Check if user is allowed to edit this date
        $check = $service->canTeacherMarkDate($this->selected_date, $weekly, Auth::user());
        $this->canEditCurrentDate = $check['allowed'];
        $this->editRestrictionReason = $check['reason'];

        // 3. Load students for this class
        $students = $this->enrolledStudents;

        // 4. Load existing records if any
        $existingAttendance = Attendance::query()
            ->where('session_id', $this->session_id)
            ->where('term_id', $this->term_id)
            ->where('classroom_id', $this->classroom_id)
            ->where('class_arm_id', $this->class_arm_id)
            ->where('department_id', $this->department_id)
            ->whereDate('date', $this->selected_date)
            ->with('records')
            ->first();

        $existingRecords = $existingAttendance
            ? $existingAttendance->records->keyBy('student_id')
            : collect();

        $this->records = [];
        foreach ($students as $student) {
            $existing = $existingRecords->get($student->id);

            $this->records[$student->id] = [
                'status' => $existing ? $existing->status : 'present', // Exception-based: default Present
                'remark' => $existing?->remark,
                'arrival_time' => $existing?->arrival_time,
            ];
        }
    }

    /**
     * Mark all students on the current day as Present.
     */
    public function markAllPresent(): void
    {
        if (! $this->canEditCurrentDate) {
            Notification::make()->title('Editing is locked for this date.')->warning()->send();
            return;
        }

        foreach ($this->records as $studentId => $data) {
            $this->records[$studentId]['status'] = 'present';
        }

        Notification::make()->title('All students marked as Present.')->info()->send();
    }

    /**
     * Set a student's status directly.
     */
    public function setStudentStatus(int $studentId, string $status): void
    {
        if (! $this->canEditCurrentDate) {
            Notification::make()->title('Editing is locked for this date.')->warning()->send();
            return;
        }

        if (isset($this->records[$studentId])) {
            $this->records[$studentId]['status'] = $status;
        }
    }

    /**
     * Save daily roll call.
     */
    public function saveDailyAttendance(): void
    {
        if (! $this->canEditCurrentDate) {
            Notification::make()->title($this->editRestrictionReason ?? 'Cannot edit attendance for this date.')->warning()->send();
            return;
        }

        $service = app(AttendanceService::class);
        $weeks = $this->weeks;
        $weekData = $weeks[$this->selected_week] ?? null;
        $startDate = $weekData ? Carbon::parse($weekData['start_date']) : Carbon::parse($this->selected_date)->startOfWeek();
        $endDate = $weekData ? Carbon::parse($weekData['end_date']) : Carbon::parse($this->selected_date)->endOfWeek();

        $weekly = $service->getOrCreateWeeklyAttendance(
            $this->session_id,
            $this->term_id,
            $this->classroom_id,
            $this->class_arm_id,
            $this->department_id,
            $this->selected_week,
            $startDate,
            $endDate
        );

        $service->saveDailyAttendance(
            $this->session_id,
            $this->term_id,
            $this->classroom_id,
            $this->class_arm_id,
            $this->department_id,
            $this->selected_date,
            Auth::id(),
            $this->records,
            $weekly
        );

        Notification::make()
            ->title('Attendance saved successfully!')
            ->body('Daily roll-call recorded for ' . Carbon::parse($this->selected_date)->format('l, M d, Y'))
            ->success()
            ->send();
    }

    /**
     * Submit and lock weekly attendance.
     */
    public function submitWeeklyAttendance(): void
    {
        $service = app(AttendanceService::class);
        $weeks = $this->weeks;
        $weekData = $weeks[$this->selected_week] ?? null;
        $startDate = $weekData ? Carbon::parse($weekData['start_date']) : Carbon::parse($this->selected_date)->startOfWeek();
        $endDate = $weekData ? Carbon::parse($weekData['end_date']) : Carbon::parse($this->selected_date)->endOfWeek();

        $weekly = $service->getOrCreateWeeklyAttendance(
            $this->session_id,
            $this->term_id,
            $this->classroom_id,
            $this->class_arm_id,
            $this->department_id,
            $this->selected_week,
            $startDate,
            $endDate
        );

        // First save current day records if editable
        if ($this->canEditCurrentDate && ! empty($this->records)) {
            $service->saveDailyAttendance(
                $this->session_id,
                $this->term_id,
                $this->classroom_id,
                $this->class_arm_id,
                $this->department_id,
                $this->selected_date,
                Auth::id(),
                $this->records,
                $weekly
            );
        }

        $service->submitWeeklyAttendance($weekly, Auth::id());

        $this->loadDayAttendance();

        Notification::make()
            ->title("Week {$this->selected_week} attendance submitted and locked!")
            ->body('Attendance for this week is now locked. Contact an Administrator if any corrections are needed.')
            ->success()
            ->send();
    }

    public function getWeeksProperty(): array
    {
        if (! $this->term_id) {
            return [];
        }

        $term = Term::find($this->term_id);
        if (! $term) {
            return [];
        }

        return app(AttendanceService::class)->getTermWeeks($term);
    }

    /**
     * Return 5 school days (Mon-Fri) for the selected week with status metadata.
     *
     * @return array<int, array{date: string, day_name: string, formatted_date: string, is_today: bool, is_selected: bool, is_future: bool, is_holiday: bool, holiday_title: ?string, has_records: bool}>
     */
    public function getWeekDaysProperty(): array
    {
        $weeks = $this->weeks;
        $weekData = $weeks[$this->selected_week] ?? null;

        if (! $weekData) {
            return [];
        }

        $monday = Carbon::parse($weekData['start_date']);
        $days = [];
        $service = app(AttendanceService::class);
        $today = now()->startOfDay();

        for ($i = 0; $i < 5; $i++) {
            $day = $monday->copy()->addDays($i);
            $dayStr = $day->toDateString();

            $dayCheck = $service->checkSchoolDay($day, $this->term_id);
            $hasAttendance = Attendance::query()
                ->where('session_id', $this->session_id)
                ->where('term_id', $this->term_id)
                ->where('classroom_id', $this->classroom_id)
                ->where('class_arm_id', $this->class_arm_id)
                ->where('department_id', $this->department_id)
                ->whereDate('date', $dayStr)
                ->exists();

            $days[] = [
                'date' => $dayStr,
                'day_name' => $day->format('D'),
                'formatted_date' => $day->format('M d'),
                'is_today' => $day->isSameDay($today),
                'is_selected' => $dayStr === $this->selected_date,
                'is_future' => $day->startOfDay()->isAfter($today),
                'is_holiday' => ! $dayCheck['is_school_day'],
                'holiday_title' => $dayCheck['holiday']?->title,
                'has_records' => $hasAttendance,
            ];
        }

        return $days;
    }

    public function getEnrolledStudentsProperty(): Collection
    {
        if (! $this->classroom_id || ! $this->session_id) {
            return collect();
        }

        return Student::query()
            ->join('student_enrollments', 'students.id', '=', 'student_enrollments.student_id')
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->where('student_enrollments.classroom_id', $this->classroom_id)
            ->where('student_enrollments.session_id', $this->session_id)
            ->when($this->class_arm_id, fn ($q) => $q->where('student_enrollments.class_arm_id', $this->class_arm_id))
            ->when($this->department_id, fn ($q) => $q->where('student_enrollments.department_id', $this->department_id))
            ->where('students.status', 'active')
            ->with('user')
            ->select('students.*')
            ->orderBy('users.surname')
            ->orderBy('users.first_name')
            ->get();
    }

    /**
     * Compute real-time count badges for today's roster.
     *
     * @return array{present: int, absent: int, late: int, excused: int, total: int}
     */
    public function getCountsProperty(): array
    {
        $present = 0;
        $absent = 0;
        $late = 0;
        $excused = 0;

        foreach ($this->records as $item) {
            $status = $item['status'] ?? 'present';
            if ($status === 'present') {
                $present++;
            } elseif ($status === 'absent') {
                $absent++;
            } elseif ($status === 'late') {
                $late++;
            } elseif ($status === 'excused') {
                $excused++;
            }
        }

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'total' => count($this->records),
        ];
    }
}
