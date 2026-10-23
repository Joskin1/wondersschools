<?php

declare(strict_types=1);

use App\Filament\Pages\ManageReportCardSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role'             => 'admin',
        'is_primary_admin' => true,
        'is_active'        => true,
    ]);

    $this->actingAs($this->admin);
});

test('admin can access ManageReportCardSettings page with seeded defaults', function () {
    Livewire::test(ManageReportCardSettings::class)
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'rc_show_school_logo'            => true,
            'rc_show_school_name'            => true,
            'rc_show_student_photo'          => true,
            'rc_show_admission_number'       => true,
            'rc_show_students_examined'      => true,
            'rc_show_subject_position'       => true,
            'rc_show_subject_teacher_remark' => true,
            'rc_show_performance_summary'    => true,
            'rc_show_term_percentage'        => true,
            'rc_show_affective_domain'       => true,
            'rc_show_psychomotor_skills'     => true,
            'rc_show_teacher_signature'      => true,
            'rc_show_school_stamp'           => true,
            'rc_page_margin_preset'          => 'standard',
        ]);
});

test('student user without permission cannot access Report Card Settings page', function () {
    $studentUser = User::factory()->create([
        'role'             => 'student',
        'is_primary_admin' => false,
        'is_active'        => true,
    ]);

    $this->actingAs($studentUser);

    expect(ManageReportCardSettings::canAccess())->toBeFalse();
});

test('admin can toggle off specific report card elements and save to database', function () {
    Livewire::test(ManageReportCardSettings::class)
        ->fillForm([
            'rc_show_gender'            => false,
            'rc_show_dob'               => false,
            'rc_show_students_examined' => false,
            'rc_show_subject_position'  => false,
            'rc_show_affective_domain'  => false,
            'rc_show_school_stamp'      => false,
            'rc_page_margin_preset'     => 'generous',
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified('Report card settings saved successfully');

    expect(Setting::where('key', 'rc_show_gender')->value('value'))->toBe('0')
        ->and(Setting::where('key', 'rc_show_dob')->value('value'))->toBe('0')
        ->and(Setting::where('key', 'rc_show_students_examined')->value('value'))->toBe('0')
        ->and(Setting::where('key', 'rc_show_subject_position')->value('value'))->toBe('0')
        ->and(Setting::where('key', 'rc_show_affective_domain')->value('value'))->toBe('0')
        ->and(Setting::where('key', 'rc_show_school_stamp')->value('value'))->toBe('0')
        ->and(Setting::where('key', 'rc_page_margin_preset')->value('value'))->toBe('generous');
});

test('admin can trigger enable_all header action', function () {
    // Set some to 0 first
    Setting::updateOrCreate(['key' => 'rc_show_gender'], ['value' => '0']);
    Setting::updateOrCreate(['key' => 'rc_show_students_examined'], ['value' => '0']);

    Livewire::test(ManageReportCardSettings::class)
        ->callAction('enable_all')
        ->assertSchemaStateSet([
            'rc_show_gender'            => true,
            'rc_show_students_examined' => true,
        ]);
});

test('admin can trigger reset_defaults header action', function () {
    Setting::updateOrCreate(['key' => 'rc_page_margin_preset'], ['value' => 'compact']);

    Livewire::test(ManageReportCardSettings::class)
        ->callAction('reset_defaults')
        ->assertSchemaStateSet([
            'rc_page_margin_preset' => 'standard',
            'rc_show_school_logo'   => true,
        ]);
});
