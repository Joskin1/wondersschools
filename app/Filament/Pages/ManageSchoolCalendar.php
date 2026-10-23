<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\ClassWeeklyAttendance;
use App\Models\Classroom;
use App\Models\SchoolHoliday;
use App\Models\Session;
use App\Models\Term;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ManageSchoolCalendar extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $title = 'Calendar & Attendance';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.manage-school-calendar';

    public ?int $session_id = null;
    public ?int $term_id = null;

    // Term dates state
    public ?string $term_start_date = null;
    public ?string $term_end_date = null;

    // Holiday form state
    public string $holiday_title = '';
    public ?string $holiday_start_date = null;
    public ?string $holiday_end_date = null;
    public ?string $holiday_description = null;

    // Compliance state
    public int $selected_week = 1;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && ($user->isSudo() || $user->canAccessAdminPage('school_calendar'));
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

        $this->loadTermData();
    }

    public function updatedSessionId(): void
    {
        $activeTerm = Term::where('session_id', $this->session_id)
            ->where('is_active', true)
            ->first() ?? Term::where('session_id', $this->session_id)->first();

        $this->term_id = $activeTerm?->id;
        $this->loadTermData();
    }

    public function updatedTermId(): void
    {
        $this->loadTermData();
    }

    public function loadTermData(): void
    {
        if (! $this->term_id) {
            $this->term_start_date = null;
            $this->term_end_date = null;
            return;
        }

        $term = Term::find($this->term_id);
        if ($term) {
            $this->term_start_date = $term->start_date?->format('Y-m-d');
            $this->term_end_date = $term->end_date?->format('Y-m-d');

            $weeks = app(AttendanceService::class)->getTermWeeks($term);
            foreach ($weeks as $wNum => $wData) {
                if ($wData['is_current']) {
                    $this->selected_week = $wNum;
                    break;
                }
            }
        }
    }

    public function saveTermDates(): void
    {
        if (! $this->term_id) {
            Notification::make()->title('Please select a term first.')->warning()->send();
            return;
        }

        $this->validate([
            'term_start_date' => 'required|date',
            'term_end_date' => 'required|date|after_or_equal:term_start_date',
        ]);

        $term = Term::findOrFail($this->term_id);
        $term->update([
            'start_date' => $this->term_start_date,
            'end_date' => $this->term_end_date,
        ]);

        Notification::make()
            ->title('Term dates updated successfully!')
            ->success()
            ->send();
    }

    public function addHoliday(): void
    {
        if (! $this->term_id || ! $this->session_id) {
            Notification::make()->title('Select session and term first.')->warning()->send();
            return;
        }

        $this->validate([
            'holiday_title' => 'required|string|max:100',
            'holiday_start_date' => 'required|date',
            'holiday_end_date' => 'required|date|after_or_equal:holiday_start_date',
            'holiday_description' => 'nullable|string|max:255',
        ]);

        SchoolHoliday::create([
            'session_id' => $this->session_id,
            'term_id' => $this->term_id,
            'title' => trim($this->holiday_title),
            'start_date' => $this->holiday_start_date,
            'end_date' => $this->holiday_end_date,
            'description' => trim((string) $this->holiday_description) ?: null,
        ]);

        $this->reset(['holiday_title', 'holiday_start_date', 'holiday_end_date', 'holiday_description']);

        Notification::make()
            ->title('Holiday / Non-school day added successfully!')
            ->success()
            ->send();
    }

    public function deleteHoliday(int $holidayId): void
    {
        $holiday = SchoolHoliday::where('term_id', $this->term_id)->find($holidayId);
        if ($holiday) {
            $holiday->delete();
            Notification::make()->title('Holiday removed.')->info()->send();
        }
    }

    public function unlockClassWeek(int $weeklyAttendanceId): void
    {
        $weekly = ClassWeeklyAttendance::find($weeklyAttendanceId);
        if (! $weekly) {
            Notification::make()->title('Record not found.')->warning()->send();
            return;
        }

        app(AttendanceService::class)->unlockWeeklyAttendance($weekly, Auth::id());

        Notification::make()
            ->title("Week {$weekly->week_number} attendance unlocked!")
            ->body('The class teacher can now edit and re-submit this week’s attendance.')
            ->success()
            ->send();
    }

    public function getHolidaysProperty(): Collection
    {
        if (! $this->term_id) {
            return collect();
        }

        return SchoolHoliday::query()
            ->where('term_id', $this->term_id)
            ->orderBy('start_date')
            ->get();
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

    public function getClassComplianceProperty(): Collection
    {
        if (! $this->session_id || ! $this->term_id) {
            return collect();
        }

        $classrooms = Classroom::where('is_active', true)
            ->with('arms')
            ->orderBy('class_order')
            ->get();

        $allDepartments = \App\Models\Department::where('is_active', true)->get();
        $complianceList = collect();

        foreach ($classrooms as $classroom) {
            $arms = $classroom->arms->isNotEmpty() ? $classroom->arms : [null];
            $departments = ($classroom->has_departments && $allDepartments->isNotEmpty()) ? $allDepartments : [null];

            foreach ($arms as $arm) {
                foreach ($departments as $department) {
                    $armId = $arm?->id;
                    $deptId = $department?->id;

                    $label = $classroom->name;
                    if ($arm) {
                        $label .= " — {$arm->name}";
                    }
                    if ($department) {
                        $label .= " ({$department->name})";
                    }

                    $weekly = ClassWeeklyAttendance::query()
                        ->where('session_id', $this->session_id)
                        ->where('term_id', $this->term_id)
                        ->where('classroom_id', $classroom->id)
                        ->where('class_arm_id', $armId)
                        ->where('department_id', $deptId)
                        ->where('week_number', $this->selected_week)
                        ->with(['submitter', 'unlocker'])
                        ->first();

                    $complianceList->push([
                        'classroom_id' => $classroom->id,
                        'class_arm_id' => $armId,
                        'department_id' => $deptId,
                        'class_name' => $label,
                        'weekly_id' => $weekly?->id,
                        'status' => $weekly?->status ?? 'not_started',
                        'submitted_by' => $weekly?->submitter?->full_name,
                        'submitted_at' => $weekly?->submitted_at?->format('M d, Y h:i A'),
                        'unlocked_by' => $weekly?->unlocker?->full_name,
                        'unlocked_at' => $weekly?->unlocked_at?->format('M d, Y h:i A'),
                    ]);
                }
            }
        }

        return $complianceList;
    }
}
