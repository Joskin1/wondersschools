<?php

use App\Filament\Resources\DesktopDeviceResource;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\DesktopDevice;
use App\Models\Session;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createTestSchoolContext(): array
{
    $session = Session::create([
        'name' => '2026-2027',
        'start_year' => 2026,
        'end_year' => 2027,
        'is_active' => true,
    ]);

    $term = Term::create([
        'session_id' => $session->id,
        'name' => 'First Term',
        'order' => 1,
        'start_date' => now()->startOfWeek()->toDateString(),
        'end_date' => now()->addWeeks(12)->endOfWeek()->toDateString(),
        'is_active' => true,
    ]);

    $admin = User::create([
        'name' => 'Admin User',
        'surname' => 'Admin',
        'first_name' => 'School',
        'email' => 'admin@wonders.test',
        'password' => Hash::make('secret123'),
        'role' => 'admin',
        'is_active' => true,
        'allowed_admin_pages' => ['*'],
    ]);

    $teacher = User::create([
        'name' => 'Teacher John',
        'surname' => 'John',
        'first_name' => 'Teacher',
        'email' => 'teacher@wonders.test',
        'password' => Hash::make('secret123'),
        'role' => 'teacher',
        'is_active' => true,
    ]);

    $classroom = Classroom::create([
        'name' => 'SS 2 Diamond',
        'class_order' => 2,
        'is_active' => true,
    ]);

    $student = Student::create([
        'full_name' => 'David Adeleke',
        'surname' => 'Adeleke',
        'first_name' => 'David',
        'admission_number' => 'ACC/2026/042',
        'status' => 'active',
    ]);

    StudentEnrollment::create([
        'student_id' => $student->id,
        'classroom_id' => $classroom->id,
        'session_id' => $session->id,
    ]);

    return compact('session', 'term', 'admin', 'teacher', 'classroom', 'student');
}

describe('Desktop Hardware Pairing API', function () {
    it('successfully pairs a hardware device with valid admin credentials', function () {
        $ctx = createTestSchoolContext();

        $response = $this->postJson('/api/v1/desktop/pair', [
            'email' => 'admin@wonders.test',
            'password' => 'secret123',
            'device_name' => 'Gate Laptop 1',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'device_token',
                'school' => [
                    'name',
                    'active_session' => ['id', 'name'],
                    'active_term' => ['id', 'name'],
                ],
            ]);

        expect($response->json('status'))->toBe('success');
        expect($response->json('device_token'))->toStartWith('wnd_dev_');
        expect(DesktopDevice::count())->toBe(1);

        $device = DesktopDevice::first();
        expect($device->device_name)->toBe('Gate Laptop 1');
        expect($device->is_active)->toBeTrue();
        expect($device->paired_by)->toBe($ctx['admin']->id);
    });

    it('rejects pairing with invalid credentials', function () {
        createTestSchoolContext();

        $response = $this->postJson('/api/v1/desktop/pair', [
            'email' => 'admin@wonders.test',
            'password' => 'wrong-password',
            'device_name' => 'Gate Laptop 1',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
            ]);

        expect(DesktopDevice::count())->toBe(0);
    });

    it('rejects pairing when user is not an admin', function () {
        createTestSchoolContext();

        $response = $this->postJson('/api/v1/desktop/pair', [
            'email' => 'teacher@wonders.test',
            'password' => 'secret123',
            'device_name' => 'Gate Laptop 1',
        ]);

        $response->assertStatus(401);
        expect(DesktopDevice::count())->toBe(0);
    });
});

describe('Desktop Authentication Middleware & Roster Sync', function () {
    it('rejects unauthenticated requests to protected desktop endpoints', function () {
        $response = $this->getJson('/api/v1/desktop/roster');
        $response->assertStatus(401);
    });

    it('rejects requests with an invalid bearer token', function () {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token_123')
            ->getJson('/api/v1/desktop/roster');

        $response->assertStatus(401);
    });

    it('returns roster with students and staff for valid paired device', function () {
        $ctx = createTestSchoolContext();

        $tokenData = DesktopDevice::generateToken();
        $device = DesktopDevice::create([
            'device_name' => 'Gate Laptop 1',
            'device_token_hash' => $tokenData['hash'],
            'device_token_prefix' => $tokenData['prefix'],
            'paired_by' => $ctx['admin']->id,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$tokenData['plain']}")
            ->getJson('/api/v1/desktop/roster');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'count',
                'roster' => [
                    '*' => [
                        'central_id',
                        'type',
                        'admission_number',
                        'surname',
                        'first_name',
                        'classroom',
                        'department',
                        'primary_template',
                        'backup_template',
                    ],
                ],
            ]);

        $roster = collect($response->json('roster'));
        $studentItem = $roster->firstWhere('type', 'student');
        expect($studentItem)->not->toBeNull();
        expect($studentItem['central_id'])->toBe($ctx['student']->id);
        expect($studentItem['admission_number'])->toBe('ACC/2026/042');
        expect($studentItem['classroom'])->toBe('SS 2 Diamond');

        $staffItem = $roster->firstWhere('central_id', $ctx['teacher']->id);
        expect($staffItem)->not->toBeNull();
        expect($staffItem['type'])->toBe('staff');
    });

    it('blocks access if device has been revoked', function () {
        $ctx = createTestSchoolContext();

        $tokenData = DesktopDevice::generateToken();
        $device = DesktopDevice::create([
            'device_name' => 'Gate Laptop 1',
            'device_token_hash' => $tokenData['hash'],
            'device_token_prefix' => $tokenData['prefix'],
            'paired_by' => $ctx['admin']->id,
            'is_active' => false, // Revoked
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$tokenData['plain']}")
            ->getJson('/api/v1/desktop/roster');

        $response->assertStatus(401);
    });
});

describe('Desktop Biometric Enrollment Cloud Backup', function () {
    it('saves enrolled biometric templates for a student', function () {
        $ctx = createTestSchoolContext();

        $tokenData = DesktopDevice::generateToken();
        DesktopDevice::create([
            'device_name' => 'Enrollment Station',
            'device_token_hash' => $tokenData['hash'],
            'device_token_prefix' => $tokenData['prefix'],
            'paired_by' => $ctx['admin']->id,
            'is_active' => true,
        ]);

        $primaryTemplate = base64_encode('ANSI_378_PRIMARY_SAMPLE');
        $backupTemplate = base64_encode('ANSI_378_BACKUP_SAMPLE');

        $response = $this->withHeader('Authorization', "Bearer {$tokenData['plain']}")
            ->postJson('/api/v1/desktop/biometrics/enroll', [
                'central_id' => $ctx['student']->id,
                'user_type' => 'student',
                'primary_template' => $primaryTemplate,
                'backup_template' => $backupTemplate,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'central_id' => $ctx['student']->id,
                'user_type' => 'student',
            ]);

        $freshStudent = $ctx['student']->fresh();
        expect($freshStudent->primary_biometric_template)->toBe($primaryTemplate);
        expect($freshStudent->backup_biometric_template)->toBe($backupTemplate);
        expect($freshStudent->biometric_enrolled_at)->not->toBeNull();
    });

    it('saves enrolled biometric templates for a staff member', function () {
        $ctx = createTestSchoolContext();

        $tokenData = DesktopDevice::generateToken();
        DesktopDevice::create([
            'device_name' => 'Enrollment Station',
            'device_token_hash' => $tokenData['hash'],
            'device_token_prefix' => $tokenData['prefix'],
            'paired_by' => $ctx['admin']->id,
            'is_active' => true,
        ]);

        $primaryTemplate = base64_encode('STAFF_PRIMARY_TEMPLATE');

        $response = $this->withHeader('Authorization', "Bearer {$tokenData['plain']}")
            ->postJson('/api/v1/desktop/biometrics/enroll', [
                'central_id' => $ctx['teacher']->id,
                'user_type' => 'staff',
                'primary_template' => $primaryTemplate,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'central_id' => $ctx['teacher']->id,
                'user_type' => 'staff',
            ]);

        $freshTeacher = $ctx['teacher']->fresh();
        expect($freshTeacher->primary_biometric_template)->toBe($primaryTemplate);
        expect($freshTeacher->backup_biometric_template)->toBeNull();
        expect($freshTeacher->biometric_enrolled_at)->not->toBeNull();
    });
});

describe('Batch Attendance Ingestion API', function () {
    it('ingests student arrival and departure logs into attendances and attendance_records', function () {
        $ctx = createTestSchoolContext();

        $tokenData = DesktopDevice::generateToken();
        $device = DesktopDevice::create([
            'device_name' => 'Gate Scanner 1',
            'device_token_hash' => $tokenData['hash'],
            'device_token_prefix' => $tokenData['prefix'],
            'paired_by' => $ctx['admin']->id,
            'is_active' => true,
        ]);

        $date = '2026-10-23';

        $response = $this->withHeader('Authorization', "Bearer {$tokenData['plain']}")
            ->postJson('/api/v1/desktop/attendance/batch', [
                'session_id' => $ctx['session']->id,
                'term_id' => $ctx['term']->id,
                'records' => [
                    [
                        'central_id' => $ctx['student']->id,
                        'user_type' => 'student',
                        'date' => $date,
                        'arrival_time' => '07:42:15',
                        'departure_time' => '14:30:00',
                    ],
                    [
                        'central_id' => $ctx['teacher']->id,
                        'user_type' => 'staff',
                        'date' => $date,
                        'arrival_time' => '07:25:00',
                        'departure_time' => '15:10:00',
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'synced_count' => 2,
            ]);

        // Verify Student Daily Attendance
        $attendance = Attendance::where('classroom_id', $ctx['classroom']->id)
            ->whereDate('date', $date)
            ->first();

        expect($attendance)->not->toBeNull();
        expect($attendance->session_id)->toBe($ctx['session']->id);

        $record = AttendanceRecord::where('attendance_id', $attendance->id)
            ->where('student_id', $ctx['student']->id)
            ->first();

        expect($record)->not->toBeNull();
        expect($record->status)->toBe('present');
        expect($record->arrival_time)->toBe('07:42:15');
        expect($record->departure_time)->toBe('14:30:00');

        // Verify Staff Daily Attendance
        $staffRecord = StaffAttendance::where('user_id', $ctx['teacher']->id)
            ->whereDate('date', $date)
            ->first();

        expect($staffRecord)->not->toBeNull();
        expect($staffRecord->status)->toBe('present');
        expect($staffRecord->arrival_time)->toBe('07:25:00');
        expect($staffRecord->departure_time)->toBe('15:10:00');
        expect($staffRecord->recorded_by_device_id)->toBe($device->id);

        // Verify Device Last Synced Timestamp updated
        expect($device->fresh()->last_synced_at)->not->toBeNull();
    });
});

describe('Filament Desktop Devices Admin Management', function () {
    it('allows school admin to view paired devices in admin panel', function () {
        $ctx = createTestSchoolContext();

        $tokenData = DesktopDevice::generateToken();
        $device = DesktopDevice::create([
            'device_name' => 'Gate Scanner Front',
            'device_token_hash' => $tokenData['hash'],
            'device_token_prefix' => $tokenData['prefix'],
            'paired_by' => $ctx['admin']->id,
            'is_active' => true,
        ]);

        $this->actingAs($ctx['admin']);

        Livewire::test(DesktopDeviceResource\Pages\ListDesktopDevices::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$device]);
    });
});

describe('Student Information Biometric Integration', function () {
    it('verifies a student identity across school checkpoints via API', function () {
        $ctx = createTestSchoolContext();

        $tokenData = DesktopDevice::generateToken();
        DesktopDevice::create([
            'device_name' => 'Exam Hall Scanner',
            'device_token_hash' => $tokenData['hash'],
            'device_token_prefix' => $tokenData['prefix'],
            'paired_by' => $ctx['admin']->id,
            'is_active' => true,
        ]);

        $ctx['student']->update([
            'primary_biometric_template' => 'EXAM_VERIFIED_TEMPLATE',
            'biometric_enrolled_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$tokenData['plain']}")
            ->postJson('/api/v1/desktop/students/verify', [
                'admission_number' => 'ACC/2026/042',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'student' => [
                    'central_id' => $ctx['student']->id,
                    'admission_number' => 'ACC/2026/042',
                    'classroom' => 'SS 2 Diamond',
                    'is_biometric_enrolled' => true,
                ],
            ]);
    });

    it('displays biometric enrollment status in StudentResource table and allows viewing profile', function () {
        $ctx = createTestSchoolContext();

        $ctx['student']->update([
            'primary_biometric_template' => 'VALID_MINUTIAE_TEMPLATE',
            'biometric_enrolled_at' => now(),
        ]);

        $this->actingAs($ctx['admin']);

        Livewire::test(\App\Filament\Resources\StudentResource\Pages\ListStudents::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$ctx['student']]);

        expect($ctx['student']->isBiometricEnrolled())->toBeTrue();

        $ctx['student']->clearBiometrics();
        expect($ctx['student']->fresh()->isBiometricEnrolled())->toBeFalse();
    });
});

