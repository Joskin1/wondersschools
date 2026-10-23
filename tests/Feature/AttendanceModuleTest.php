<?php

use App\Filament\Pages\ManageSchoolCalendar;
use App\Filament\Teacher\Pages\ClassAttendance;
use App\Filament\Teacher\Pages\ProcessClassResults;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\ClassTeacherAssignment;
use App\Models\ClassWeeklyAttendance;
use App\Models\Classroom;
use App\Models\SchoolHoliday;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function setupAttendanceTestContext(): array
{
    $session = Session::create([
        'name' => '2026-2027',
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    // Term starting on Monday of current week
    $monday = now()->startOfWeek(Carbon::MONDAY);
    $term = Term::create([
        'session_id' => $session->id,
        'name' => 'First Term',
        'order' => 1,
        'start_date' => $monday->toDateString(),
        'end_date' => $monday->copy()->addWeeks(12)->endOfWeek(Carbon::FRIDAY)->toDateString(),
        'is_active' => true,
    ]);

    $classroom = Classroom::create([
        'name' => 'JSS 2 Diamond',
        'class_order' => 2,
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
        'allowed_admin_pages' => ['*'],
    ]);

    $teacher = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
    ]);

    ClassTeacherAssignment::create([
        'teacher_id' => $teacher->id,
        'class_id' => $classroom->id,
        'session_id' => $session->id,
    ]);

    // Create 3 active students
    $students = [];
    for ($i = 1; $i <= 3; $i++) {
        $studentUser = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'full_name' => "Student {$i} Test",
            'admission_number' => "JSS-00{$i}",
            'status' => 'active',
        ]);

        StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'session_id' => $session->id,
        ]);

        $students[] = $student;
    }

    return compact('session', 'term', 'classroom', 'admin', 'teacher', 'students', 'monday');
}

describe('Admin School Calendar & Holiday Management', function () {
    it('allows admin to set term dates and declare holidays', function () {
        $ctx = setupAttendanceTestContext();

        $start = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $end = now()->startOfWeek(Carbon::MONDAY)->addWeeks(10)->toDateString();

        Livewire::actingAs($ctx['admin'])
            ->test(ManageSchoolCalendar::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('term_start_date', $start)
            ->set('term_end_date', $end)
            ->call('saveTermDates')
            ->assertNotified('Term dates updated successfully!');

        expect($ctx['term']->fresh()->start_date->toDateString())->toBe($start);
        expect($ctx['term']->fresh()->end_date->toDateString())->toBe($end);

        // Declare a holiday (e.g. Mid-term break)
        $holidayStart = now()->startOfWeek(Carbon::MONDAY)->addDays(3)->toDateString(); // Thursday
        $holidayEnd = now()->startOfWeek(Carbon::MONDAY)->addDays(4)->toDateString(); // Friday

        Livewire::actingAs($ctx['admin'])
            ->test(ManageSchoolCalendar::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('holiday_title', 'Mid-Term Break')
            ->set('holiday_start_date', $holidayStart)
            ->set('holiday_end_date', $holidayEnd)
            ->set('holiday_description', 'School closed for mid-term')
            ->call('addHoliday')
            ->assertNotified('Holiday / Non-school day added successfully!');

        $holiday = SchoolHoliday::where('term_id', $ctx['term']->id)->first();
        expect($holiday)->not->toBeNull();
        expect($holiday->title)->toBe('Mid-Term Break');
        expect($holiday->start_date->toDateString())->toBe($holidayStart);
        expect($holiday->end_date->toDateString())->toBe($holidayEnd);
    });

    it('allows admin to remove declared holidays', function () {
        $ctx = setupAttendanceTestContext();

        $holiday = SchoolHoliday::create([
            'session_id' => $ctx['session']->id,
            'term_id' => $ctx['term']->id,
            'title' => 'Test Day Off',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        Livewire::actingAs($ctx['admin'])
            ->test(ManageSchoolCalendar::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->call('deleteHoliday', $holiday->id)
            ->assertNotified('Holiday removed.');

        expect(SchoolHoliday::find($holiday->id))->toBeNull();
    });
});

describe('Teacher Class Attendance Workflow', function () {
    it('restricts attendance page access strictly to assigned class teachers and admins', function () {
        $ctx = setupAttendanceTestContext();

        $unassignedTeacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

        expect(ClassAttendance::canAccess())->toBeFalse(); // guest

        Livewire::actingAs($unassignedTeacher)
            ->test(ClassAttendance::class)
            ->assertForbidden();

        Livewire::actingAs($ctx['teacher'])
            ->test(ClassAttendance::class)
            ->assertSuccessful();

        Livewire::actingAs($ctx['admin'])
            ->test(ClassAttendance::class)
            ->assertSuccessful();
    });

    it('loads enrolled students with default Present status and allows marking exceptions', function () {
        $ctx = setupAttendanceTestContext();
        $student1 = $ctx['students'][0];
        $student2 = $ctx['students'][1];
        $monday = $ctx['monday']->toDateString();

        $component = Livewire::actingAs($ctx['teacher'])
            ->test(ClassAttendance::class)
            ->call('selectDate', $monday);

        // Exception-based: all 3 students should default to present
        expect($component->get('records')[$student1->id]['status'])->toBe('present');
        expect($component->get('records')[$student2->id]['status'])->toBe('present');

        // Teacher taps Student 2 as Absent with remark
        $component->call('setStudentStatus', $student2->id, 'absent')
            ->set("records.{$student2->id}.remark", 'Malaria fever')
            ->call('saveDailyAttendance')
            ->assertNotified('Attendance saved successfully!');

        // Verify database persistence
        $attendance = Attendance::where('classroom_id', $ctx['classroom']->id)
            ->whereDate('date', $monday)
            ->first();

        expect($attendance)->not->toBeNull();
        expect($attendance->records()->count())->toBe(3);

        $rec1 = $attendance->records()->where('student_id', $student1->id)->first();
        expect($rec1->status)->toBe('present');

        $rec2 = $attendance->records()->where('student_id', $student2->id)->first();
        expect($rec2->status)->toBe('absent');
        expect($rec2->remark)->toBe('Malaria fever');
    });

    it('provides markAllPresent fast reset action', function () {
        $ctx = setupAttendanceTestContext();
        $monday = $ctx['monday']->toDateString();

        $component = Livewire::actingAs($ctx['teacher'])
            ->test(ClassAttendance::class)
            ->call('selectDate', $monday);

        // Mark everyone absent
        foreach ($ctx['students'] as $st) {
            $component->call('setStudentStatus', $st->id, 'absent');
        }

        // Tap Mark All Present
        $component->call('markAllPresent')
            ->assertNotified('All students marked as Present.');

        foreach ($ctx['students'] as $st) {
            expect($component->get('records')[$st->id]['status'])->toBe('present');
        }
    });
});

describe('Date Constraints & Security Policies', function () {
    it('blocks teachers from marking attendance ahead of time (future dates)', function () {
        $ctx = setupAttendanceTestContext();
        $futureDate = now()->addDays(5)->toDateString();

        $component = Livewire::actingAs($ctx['teacher'])
            ->test(ClassAttendance::class)
            ->call('selectDate', $futureDate);

        expect($component->get('canEditCurrentDate'))->toBeFalse();
        expect($component->get('editRestrictionReason'))->toContain('Cannot mark attendance ahead of time');

        $component->call('saveDailyAttendance')
            ->assertNotified('Cannot mark attendance ahead of time.');
    });

    it('blocks teachers from marking attendance on admin-declared holidays', function () {
        $ctx = setupAttendanceTestContext();
        $holidayDate = $ctx['monday']->toDateString();

        SchoolHoliday::create([
            'session_id' => $ctx['session']->id,
            'term_id' => $ctx['term']->id,
            'title' => 'National Public Holiday',
            'start_date' => $holidayDate,
            'end_date' => $holidayDate,
        ]);

        $component = Livewire::actingAs($ctx['teacher'])
            ->test(ClassAttendance::class)
            ->call('selectDate', $holidayDate);

        expect($component->get('canEditCurrentDate'))->toBeFalse();
        expect($component->get('editRestrictionReason'))->toContain('National Public Holiday');

        $component->call('saveDailyAttendance')
            ->assertNotified('Holiday: National Public Holiday');
    });
});

describe('Weekly Submission & Admin Unlock Flow', function () {
    it('locks week when teacher submits, preventing further modifications until unlocked by admin', function () {
        $ctx = setupAttendanceTestContext();
        $student1 = $ctx['students'][0];
        $monday = $ctx['monday']->toDateString();

        $component = Livewire::actingAs($ctx['teacher'])
            ->test(ClassAttendance::class)
            ->set('selected_date', $monday)
            ->call('submitWeeklyAttendance')
            ->assertNotified('Week 1 attendance submitted and locked!');

        // Verify status in DB
        $weekly = ClassWeeklyAttendance::where('classroom_id', $ctx['classroom']->id)
            ->where('week_number', 1)
            ->first();

        expect($weekly)->not->toBeNull();
        expect($weekly->status)->toBe('locked');
        expect($weekly->isLocked())->toBeTrue();

        // Refresh component: should now be locked
        $component->call('loadDayAttendance');
        expect($component->get('canEditCurrentDate'))->toBeFalse();
        expect($component->get('isCurrentWeekLocked'))->toBeTrue();

        // Attempting to modify should be rejected
        $component->call('setStudentStatus', $student1->id, 'absent')
            ->assertNotified('Editing is locked for this date.');

        // Now Admin unlocks the week
        Livewire::actingAs($ctx['admin'])
            ->test(ManageSchoolCalendar::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('selected_week', 1)
            ->call('unlockClassWeek', $weekly->id)
            ->assertNotified('Week 1 attendance unlocked!');

        expect($weekly->fresh()->status)->toBe('in_progress');
        expect($weekly->fresh()->isLocked())->toBeFalse();

        // Teacher can now edit again
        $teacherComp = Livewire::actingAs($ctx['teacher'])
            ->test(ClassAttendance::class)
            ->set('selected_date', $monday);

        expect($teacherComp->get('canEditCurrentDate'))->toBeTrue();
        expect($teacherComp->get('isCurrentWeekLocked'))->toBeFalse();
    });
});

describe('End-of-Term Report Card Synchronization', function () {
    it('automatically syncs student times_present and class times_school_opened into results processing', function () {
        $ctx = setupAttendanceTestContext();
        $student1 = $ctx['students'][0];
        $student2 = $ctx['students'][1];
        $monday = $ctx['monday']->toDateString();

        // Record attendance: Student 1 present, Student 2 absent
        Livewire::actingAs($ctx['teacher'])
            ->test(ClassAttendance::class)
            ->set('selected_date', $monday)
            ->call('setStudentStatus', $student1->id, 'present')
            ->call('setStudentStatus', $student2->id, 'absent')
            ->call('saveDailyAttendance');

        // Teacher opens ProcessClassResults and clicks syncFromAttendance
        $resultsComponent = Livewire::actingAs($ctx['teacher'])
            ->test(ProcessClassResults::class)
            ->set('session_id', $ctx['session']->id)
            ->set('term_id', $ctx['term']->id)
            ->set('classroom_id', $ctx['classroom']->id)
            ->call('syncFromAttendance')
            ->assertNotified('Attendance metrics synchronized!');

        // Check remarks state
        $remarks = $resultsComponent->get('remarks');
        expect($remarks[$student1->id]['times_present'])->toBe(1);
        expect($remarks[$student2->id]['times_present'])->toBe(0);
        expect($resultsComponent->get('times_school_opened'))->toBe(1);

        // Save remarks & attendance
        $resultsComponent->call('saveRemarks')
            ->assertNotified('Student remarks & attendance saved successfully!');

        // Check term_results table
        $tr1 = TermResult::where('student_id', $student1->id)->where('term_id', $ctx['term']->id)->first();
        expect($tr1)->not->toBeNull();
        expect($tr1->times_present)->toBe(1);

        $tr2 = TermResult::where('student_id', $student2->id)->where('term_id', $ctx['term']->id)->first();
        expect($tr2)->not->toBeNull();
        expect($tr2->times_present)->toBe(0);
    });
});

describe('End-to-End HTTP Browser Page Rendering', function () {
    it('successfully renders Admin Calendar & Attendance page over HTTP', function () {
        $ctx = setupAttendanceTestContext();

        $response = test()->actingAs($ctx['admin'])
            ->get('/admin/manage-school-calendar');

        $response->assertSuccessful();
        $response->assertSee('Calendar & Attendance');
        $response->assertSee('Term Calendar Dates');
        $response->assertSee('Declare School Holiday');
    });

    it('successfully renders Teacher Class Attendance page over HTTP', function () {
        $ctx = setupAttendanceTestContext();

        $response = test()->actingAs($ctx['teacher'])
            ->get('/teacher/class-attendance');

        $response->assertSuccessful();
        $response->assertSee('Class Attendance');
        $response->assertSee('Daily Attendance Register');
        $response->assertSee('Mark All Present');
        $response->assertSee($ctx['students'][0]->full_name);
    });

    it('successfully renders Process Class Results page with attendance sync button over HTTP', function () {
        $ctx = setupAttendanceTestContext();

        $response = test()->actingAs($ctx['teacher'])
            ->get('/teacher/process-class-results');

        $response->assertSuccessful();
        $response->assertSee('Process Class Results');
        $response->assertSee('Sync Daily Attendance');
    });
});

