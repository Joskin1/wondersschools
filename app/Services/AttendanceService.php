<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\ClassWeeklyAttendance;
use App\Models\SchoolHoliday;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Determine if a specific date is an active school day.
     *
     * @return array{is_school_day: bool, reason: ?string, holiday: ?SchoolHoliday}
     */
    public function checkSchoolDay(string|Carbon $date, int $termId): array
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        // 1. Weekend Check (Saturday = 6, Sunday = 7)
        if ($d->dayOfWeekIso > 5) {
            return [
                'is_school_day' => false,
                'reason' => 'Weekend — School is closed.',
                'holiday' => null,
            ];
        }

        // 2. Admin Declared Holiday Check
        $holiday = SchoolHoliday::query()
            ->where('term_id', $termId)
            ->forDate($d)
            ->first();

        if ($holiday) {
            return [
                'is_school_day' => false,
                'reason' => "Holiday: {$holiday->title}",
                'holiday' => $holiday,
            ];
        }

        return [
            'is_school_day' => true,
            'reason' => null,
            'holiday' => null,
        ];
    }

    /**
     * Check if a teacher can mark attendance for a specific date in a weekly attendance context.
     * Enforces:
     * - No future dates (date > today)
     * - No weekends
     * - No admin-declared holidays
     * - Must not be locked
     * - Must be within current week or unsubmitted current week (Option B)
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canTeacherMarkDate(
        string|Carbon $date,
        ClassWeeklyAttendance $weeklyAttendance,
        ?User $user = null
    ): array {
        $d = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();
        $today = now()->startOfDay();

        // Admin override can edit any date if needed
        if ($user && ($user->isAdmin() || $user->isSudo())) {
            // Admins can manage any date except future dates
            if ($d->isAfter($today)) {
                return ['allowed' => false, 'reason' => 'Cannot mark attendance for future dates.'];
            }
            return ['allowed' => true, 'reason' => null];
        }

        // 1. Locked Check
        if ($weeklyAttendance->isLocked()) {
            return [
                'allowed' => false,
                'reason' => 'This week has been submitted and locked. Contact an Administrator to unlock.',
            ];
        }

        // 2. Future Date Check (date > today)
        if ($d->isAfter($today)) {
            return [
                'allowed' => false,
                'reason' => 'Cannot mark attendance ahead of time.',
            ];
        }

        // 3. School Day & Holiday Check
        $dayStatus = $this->checkSchoolDay($d, $weeklyAttendance->term_id);
        if (! $dayStatus['is_school_day']) {
            return [
                'allowed' => false,
                'reason' => $dayStatus['reason'],
            ];
        }

        // 4. Current Week Constraint (Option B: Current week only, or past weeks are locked once passed)
        $currentWeekMonday = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekStart = $weeklyAttendance->start_date->copy()->startOfDay();

        // If this weekly attendance is from a past week and current date is in a later week
        if ($weekStart->isBefore($currentWeekMonday) && $weeklyAttendance->isLocked()) {
            return [
                'allowed' => false,
                'reason' => 'Past weeks cannot be edited once concluded.',
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Get or create a weekly attendance container for a class.
     */
    public function getOrCreateWeeklyAttendance(
        int $sessionId,
        int $termId,
        int $classroomId,
        ?int $classArmId,
        ?int $departmentId,
        int $weekNumber,
        Carbon $startDate,
        Carbon $endDate
    ): ClassWeeklyAttendance {
        return ClassWeeklyAttendance::firstOrCreate(
            [
                'session_id' => $sessionId,
                'term_id' => $termId,
                'classroom_id' => $classroomId,
                'class_arm_id' => $classArmId,
                'department_id' => $departmentId,
                'week_number' => $weekNumber,
            ],
            [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => 'in_progress',
            ]
        );
    }

    /**
     * Generate weeks metadata for a term.
     *
     * @return array<int, array{week_number: int, start_date: string, end_date: string, label: string, is_current: bool}>
     */
    public function getTermWeeks(Term $term): array
    {
        $today = now()->startOfDay();

        // Determine term start and end
        if ($term->start_date && $term->end_date) {
            $start = $term->start_date->copy()->startOfWeek(Carbon::MONDAY);
            $end = $term->end_date->copy()->endOfWeek(Carbon::FRIDAY);
        } else {
            // Default: 13 weeks span around current date or term creation
            $start = ($term->created_at ? $term->created_at->copy() : now())->startOfWeek(Carbon::MONDAY);
            $end = $start->copy()->addWeeks(12)->endOfWeek(Carbon::FRIDAY);
        }

        $weeks = [];
        $current = $start->copy();
        $weekNum = 1;

        while ($current->isBefore($end)) {
            $weekStart = $current->copy();
            $weekEnd = $current->copy()->addDays(4); // Friday

            $isCurrent = $today->betweenIncluded($weekStart, $current->copy()->endOfWeek(Carbon::SUNDAY));

            $weeks[$weekNum] = [
                'week_number' => $weekNum,
                'start_date' => $weekStart->toDateString(),
                'end_date' => $weekEnd->toDateString(),
                'label' => "Week {$weekNum} (" . $weekStart->format('M d') . ' – ' . $weekEnd->format('M d') . ')',
                'is_current' => $isCurrent,
            ];

            $current->addWeek();
            $weekNum++;
        }

        return $weeks;
    }

    /**
     * Submit and lock weekly attendance.
     */
    public function submitWeeklyAttendance(ClassWeeklyAttendance $weeklyAttendance, int $userId): void
    {
        $weeklyAttendance->lock($userId);
    }

    /**
     * Unlock a previously locked weekly attendance (Admin only).
     */
    public function unlockWeeklyAttendance(ClassWeeklyAttendance $weeklyAttendance, int $userId): void
    {
        $weeklyAttendance->unlock($userId);
    }

    /**
     * Save daily roll call records for a classroom.
     *
     * @param array<int, array{status: string, remark: ?string, arrival_time: ?string}> $records [student_id => data]
     */
    public function saveDailyAttendance(
        int $sessionId,
        int $termId,
        int $classroomId,
        ?int $classArmId,
        ?int $departmentId,
        string|Carbon $date,
        int $userId,
        array $records,
        ?ClassWeeklyAttendance $weeklyAttendance = null
    ): Attendance {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : $date;

        return DB::transaction(function () use (
            $sessionId,
            $termId,
            $classroomId,
            $classArmId,
            $departmentId,
            $dateStr,
            $userId,
            $records,
            $weeklyAttendance
        ) {
            $attendance = Attendance::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'term_id' => $termId,
                    'classroom_id' => $classroomId,
                    'class_arm_id' => $classArmId,
                    'department_id' => $departmentId,
                    'date' => $dateStr,
                ],
                [
                    'class_weekly_attendance_id' => $weeklyAttendance?->id,
                    'recorded_by' => $userId,
                ]
            );

            foreach ($records as $studentId => $data) {
                AttendanceRecord::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'student_id' => (int) $studentId,
                    ],
                    [
                        'status' => $data['status'] ?? 'present',
                        'remark' => ! empty($data['remark']) ? trim((string) $data['remark']) : null,
                        'arrival_time' => ! empty($data['arrival_time']) ? (string) $data['arrival_time'] : null,
                    ]
                );
            }

            return $attendance;
        });
    }

    /**
     * Calculate student attendance metrics for report cards.
     *
     * @return array{times_present: int, times_absent: int, times_late: int, times_excused: int, total_recorded: int}
     */
    public function calculateStudentAttendance(int $studentId, int $termId, ?int $sessionId = null): array
    {
        $query = AttendanceRecord::query()
            ->join('attendances', 'attendance_records.attendance_id', '=', 'attendances.id')
            ->where('attendance_records.student_id', $studentId)
            ->where('attendances.term_id', $termId);

        if ($sessionId) {
            $query->where('attendances.session_id', $sessionId);
        }

        $records = $query->select('attendance_records.status')->get();

        $present = $records->filter(fn ($r) => in_array($r->status, ['present', 'late'], true))->count();
        $strictlyPresent = $records->where('status', 'present')->count();
        $late = $records->where('status', 'late')->count();
        $absent = $records->where('status', 'absent')->count();
        $excused = $records->where('status', 'excused')->count();

        return [
            'times_present' => $present, // In report cards, Late is usually in school, counted towards attendance
            'strictly_present' => $strictlyPresent,
            'times_late' => $late,
            'times_absent' => $absent,
            'times_excused' => $excused,
            'total_recorded' => $records->count(),
        ];
    }

    /**
     * Calculate total distinct school days recorded for a classroom in a term.
     */
    public function calculateClassSchoolDays(
        int $classroomId,
        int $termId,
        ?int $classArmId = null,
        ?int $departmentId = null
    ): int {
        return Attendance::query()
            ->where('classroom_id', $classroomId)
            ->where('term_id', $termId)
            ->when($classArmId, fn ($q) => $q->where('class_arm_id', $classArmId))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->distinct()
            ->count('date');
    }
}
