<?php

declare(strict_types=1);

use App\Services\ReportCardSettingsService;

test('ReportCardSettingsService returns standard defaults where all elements default to true', function () {
    $defaults = ReportCardSettingsService::defaults();

    expect($defaults)->toBeArray()
        ->and($defaults['rc_show_school_logo'])->toBeTrue()
        ->and($defaults['rc_show_school_name'])->toBeTrue()
        ->and($defaults['rc_show_student_photo'])->toBeTrue()
        ->and($defaults['rc_show_student_name'])->toBeTrue()
        ->and($defaults['rc_show_admission_number'])->toBeTrue()
        ->and($defaults['rc_show_score_heads'])->toBeTrue()
        ->and($defaults['rc_show_students_examined'])->toBeTrue()
        ->and($defaults['rc_show_subject_grade'])->toBeTrue()
        ->and($defaults['rc_show_subject_position'])->toBeTrue()
        ->and($defaults['rc_show_subject_teacher_remark'])->toBeTrue()
        ->and($defaults['rc_show_performance_summary'])->toBeTrue()
        ->and($defaults['rc_show_term_percentage'])->toBeTrue()
        ->and($defaults['rc_show_affective_domain'])->toBeTrue()
        ->and($defaults['rc_show_psychomotor_skills'])->toBeTrue()
        ->and($defaults['rc_show_teacher_signature'])->toBeTrue()
        ->and($defaults['rc_show_school_stamp'])->toBeTrue()
        ->and($defaults['rc_page_margin_preset'])->toBe('standard')
        ->and($defaults['rc_page_margin_top'])->toBe(18);
});

test('ReportCardSettingsService correctly checks isEnabled with various types', function () {
    $settings = [
        'rc_show_gender' => '1',
        'rc_show_dob'    => '0',
        'rc_show_class'  => true,
        'rc_show_arm'    => false,
    ];

    expect(ReportCardSettingsService::isEnabled($settings, 'rc_show_gender'))->toBeTrue()
        ->and(ReportCardSettingsService::isEnabled($settings, 'rc_show_dob'))->toBeFalse()
        ->and(ReportCardSettingsService::isEnabled($settings, 'rc_show_class'))->toBeTrue()
        ->and(ReportCardSettingsService::isEnabled($settings, 'rc_show_arm'))->toBeFalse()
        ->and(ReportCardSettingsService::isEnabled($settings, 'non_existent_key'))->toBeTrue()
        ->and(ReportCardSettingsService::isEnabled($settings, 'non_existent_key', false))->toBeFalse();
});

test('ReportCardSettingsService generates appropriate page margin CSS', function () {
    expect(ReportCardSettingsService::getMarginCss(['rc_page_margin_preset' => 'standard']))
        ->toBe('18mm 18mm 18mm 18mm');

    expect(ReportCardSettingsService::getMarginCss(['rc_page_margin_preset' => 'generous']))
        ->toBe('22mm 20mm 22mm 20mm');

    expect(ReportCardSettingsService::getMarginCss(['rc_page_margin_preset' => 'compact']))
        ->toBe('14mm 14mm 14mm 14mm');

    expect(ReportCardSettingsService::getMarginCss([
        'rc_page_margin_preset' => 'custom',
        'rc_page_margin_top'    => 25,
        'rc_page_margin_right'  => 20,
        'rc_page_margin_bottom' => 30,
        'rc_page_margin_left'   => 15,
    ]))->toBe('25mm 20mm 30mm 15mm');
});

test('ReportCardSettingsService provides metadata for all 8 UI groups', function () {
    $groups = ReportCardSettingsService::getGroups();

    expect($groups)->toBeArray()
        ->toHaveKeys([
            'head',
            'student_info',
            'body_scores',
            'summary',
            'domains_skills',
            'remarks',
            'signatories_footer',
            'layout_margins',
        ]);
});
