<?php

declare(strict_types=1);

use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('avoos generateAdmissionNumber produces 3-digit padded sequential admission numbers', function () {
    $tenant = Tenant::create([
        'id' => 'avoos',
        'name' => 'Avoos International Schools',
    ]);
    tenancy()->initialize($tenant);
    Student::withTrashed()->forceDelete();

    $adm1 = Student::generateAdmissionNumber(1);
    expect($adm1)->toBe('AVS' . now()->format('Y') . '001');

    // Create a student with that admission number
    Student::forceCreate([
        'admission_number' => $adm1,
        'status' => 'active',
    ]);

    $adm2 = Student::generateAdmissionNumber(2);
    expect($adm2)->toBe('AVS' . now()->format('Y') . '002');
});

test('avoos:renumber-students renumbers from highest class to least class and exports credentials', function () {
    $tenant = Tenant::create([
        'id' => 'avoos',
        'name' => 'Avoos International Schools',
    ]);
    tenancy()->initialize($tenant);
    StudentEnrollment::query()->delete();
    Student::withTrashed()->forceDelete();

    $session = Session::create([
        'name' => '2025/2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'is_active' => true,
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonths(8),
    ]);

    // High class (order: 10)
    $classHigh = Classroom::create([
        'name' => 'YEAR 5',
        'class_order' => 10,
        'is_active' => true,
    ]);

    // Low class (order: 1)
    $classLow = Classroom::create([
        'name' => 'CRECHE',
        'class_order' => 1,
        'is_active' => true,
    ]);

    // Create students
    $userHigh = User::factory()->create(['name' => 'Zara High', 'surname' => 'High', 'first_name' => 'Zara']);
    $studentHigh = Student::create(['user_id' => $userHigh->id]);
    StudentEnrollment::create([
        'student_id' => $studentHigh->id,
        'classroom_id' => $classHigh->id,
        'session_id' => $session->id,
    ]);

    $userLow = User::factory()->create(['name' => 'Adam Low', 'surname' => 'Low', 'first_name' => 'Adam']);
    $studentLow = Student::create(['user_id' => $userLow->id]);
    StudentEnrollment::create([
        'student_id' => $studentLow->id,
        'classroom_id' => $classLow->id,
        'session_id' => $session->id,
    ]);

    $exportDir = sys_get_temp_dir() . '/avoos_test_export_' . uniqid();

    $this->artisan('avoos:renumber-students', [
        '--tenant' => 'avoos',
        '--export-dir' => $exportDir,
        '--password' => 'Avoos2026#',
    ])->assertSuccessful();

    $studentHigh->refresh();
    $studentLow->refresh();

    $year = now()->format('Y');

    // High class student should be #1 (AVS2026001)
    expect($studentHigh->admission_number)->toBe("AVS{$year}001");
    // Low class student should be #2 (AVS2026002)
    expect($studentLow->admission_number)->toBe("AVS{$year}002");

    // Check users
    $userHigh->refresh();
    $userLow->refresh();
    expect($userHigh->email)->toBe("avs{$year}001@student.local");
    expect(Hash::check('Avoos2026#', $userHigh->password))->toBeTrue();

    // Check files
    expect(file_exists($exportDir . '/AVOOS_Students_Login_Credentials.xlsx'))->toBeTrue();
    expect(file_exists($exportDir . '/AVOOS_Students_Login_Credentials.csv'))->toBeTrue();

    // Clean up
    @unlink($exportDir . '/AVOOS_Students_Login_Credentials.xlsx');
    @unlink($exportDir . '/AVOOS_Students_Login_Credentials.csv');
    @rmdir($exportDir);
});
