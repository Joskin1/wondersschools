<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class ReportCardSettingsService
{
    /**
     * Default values for all report card settings.
     * All options default to true so existing tenants preserve complete report cards.
     */
    public static function defaults(): array
    {
        return [
            // ── Group 1: Header & School Identity ────────────────────────────
            'rc_show_school_logo'            => true,
            'rc_show_school_name'            => true,
            'rc_show_school_address'         => true,
            'rc_show_school_contacts'        => true,
            'rc_show_school_motto'           => true,
            'rc_show_report_title'           => true,
            'rc_show_term_ribbon'            => true,

            // ── Group 2: Student Profile & Bio-Data ─────────────────────────
            'rc_show_student_photo'          => true,
            'rc_show_student_name'           => true,
            'rc_show_admission_number'       => true,
            'rc_show_class'                  => true,
            'rc_show_class_arm'              => true,
            'rc_show_department'             => true,
            'rc_show_class_size'             => true,
            'rc_show_arm_dept_population'    => true,
            'rc_show_gender'                 => true,
            'rc_show_dob'                    => true,
            'rc_show_attendance'             => true,

            // ── Group 3: Subject Assessment Table ───────────────────────────
            'rc_show_score_heads'            => true,
            'rc_show_cumulative_terms'       => true,
            'rc_show_subject_total'          => true,
            'rc_show_students_examined'      => true,
            'rc_show_subject_grade'          => true,
            'rc_show_subject_position'       => true,
            'rc_show_subject_teacher_remark' => true,

            // ── Group 4: Terminal Performance Summary ───────────────────────
            'rc_show_performance_summary'    => true,
            'rc_show_total_obtained'         => true,
            'rc_show_term_percentage'        => true,
            'rc_show_overall_position'       => true,
            'rc_show_arm_dept_position'      => true,
            'rc_show_final_grade'            => true,

            // ── Group 5: Behavioral & Practical Domains ────────────────────
            'rc_show_affective_domain'       => true,
            'rc_show_psychomotor_skills'     => true,
            'rc_show_domain_scale'           => true,

            // ── Group 6: Remarks & Resumption ──────────────────────────────
            'rc_show_class_teacher_remark'   => true,
            'rc_show_principal_remark'       => true,
            'rc_show_next_term_resumption'   => true,

            // ── Group 7: Signatories & Verification ─────────────────────────
            'rc_show_teacher_signature'      => true,
            'rc_show_principal_signature'    => true,
            'rc_show_school_stamp'           => true,
            'rc_show_footer_note'            => true,

            // ── Group 8: Margins & Spacing ──────────────────────────────────
            'rc_page_margin_preset'          => 'standard', // 'compact', 'standard', 'generous', 'custom'
            'rc_page_margin_top'             => 18,
            'rc_page_margin_bottom'          => 18,
            'rc_page_margin_left'            => 18,
            'rc_page_margin_right'           => 18,
        ];
    }

    /**
     * Get list of all setting keys.
     *
     * @return list<string>
     */
    public static function getAllKeys(): array
    {
        return array_keys(static::defaults());
    }

    /**
     * Load all settings merged with defaults.
     *
     * @return array<string, mixed>
     */
    public static function loadSettings(): array
    {
        $defaults = static::defaults();
        $stored = Setting::whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->toArray();

        $result = [];
        foreach ($defaults as $key => $defaultVal) {
            if (! array_key_exists($key, $stored)) {
                $result[$key] = $defaultVal;
                continue;
            }

            $raw = $stored[$key];
            if (is_bool($defaultVal)) {
                $result[$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_int($defaultVal)) {
                $result[$key] = is_numeric($raw) ? (int) $raw : $defaultVal;
            } else {
                $result[$key] = $raw;
            }
        }

        return $result;
    }

    /**
     * Check if a specific toggle is enabled.
     */
    public static function isEnabled(array $settings, string $key, ?bool $default = null): bool
    {
        $defaults = static::defaults();
        $fallback = $default ?? ($defaults[$key] ?? true);

        if (! array_key_exists($key, $settings)) {
            return (bool) $fallback;
        }

        $val = $settings[$key];
        if (is_bool($val)) {
            return $val;
        }

        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Calculate margin CSS string for @page rule.
     */
    public static function getMarginCss(array $settings): string
    {
        $preset = $settings['rc_page_margin_preset'] ?? 'standard';

        switch ($preset) {
            case 'compact':
                return '14mm 14mm 14mm 14mm';
            case 'generous':
                return '22mm 20mm 22mm 20mm';
            case 'custom':
                $top    = (int) ($settings['rc_page_margin_top'] ?? 18);
                $right  = (int) ($settings['rc_page_margin_right'] ?? 18);
                $bottom = (int) ($settings['rc_page_margin_bottom'] ?? 18);
                $left   = (int) ($settings['rc_page_margin_left'] ?? 18);
                return "{$top}mm {$right}mm {$bottom}mm {$left}mm";
            case 'standard':
            default:
                return '18mm 18mm 18mm 18mm';
        }
    }

    /**
     * Group definitions and metadata for the Filament UI.
     */
    public static function getGroups(): array
    {
        return [
            'head' => [
                'title'       => 'Header & School Identity',
                'description' => 'Control school branding, crest, contact info, motto, and header banner.',
                'icon'        => 'heroicon-o-building-library',
                'fields'      => [
                    'rc_show_school_logo' => [
                        'label'       => 'Show School Crest / Logo',
                        'helper'      => 'Displays the uploaded school crest or initials badge in the header.',
                    ],
                    'rc_show_school_name' => [
                        'label'       => 'Show School Name',
                        'helper'      => 'Displays the official institution title in authoritative bold header text.',
                    ],
                    'rc_show_school_address' => [
                        'label'       => 'Show School Campus Address',
                        'helper'      => 'Displays the physical campus address directly beneath the school name.',
                    ],
                    'rc_show_school_contacts' => [
                        'label'       => 'Show School Contact Details',
                        'helper'      => 'Displays the registry telephone, email, and website link.',
                    ],
                    'rc_show_school_motto' => [
                        'label'       => 'Show School Motto',
                        'helper'      => 'Displays the italicized school motto or ethos statement.',
                    ],
                    'rc_show_report_title' => [
                        'label'       => 'Show Report Title Badge',
                        'helper'      => 'Displays the pill badge e.g. "OFFICIAL TERMINAL REPORT CARD".',
                    ],
                    'rc_show_term_ribbon' => [
                        'label'       => 'Show Academic Term & Session Ribbon',
                        'helper'      => 'Displays the colored horizontal band stating the term and session.',
                    ],
                ],
            ],

            'student_info' => [
                'title'       => 'Student Profile & Bio-Data',
                'description' => 'Configure student personal identifiers, classroom, arm, department, and attendance info.',
                'icon'        => 'heroicon-o-user-circle',
                'fields'      => [
                    'rc_show_student_photo' => [
                        'label'       => 'Show Student Passport Photo',
                        'helper'      => 'Displays the student passport photo (or placeholder box) on the top right.',
                    ],
                    'rc_show_student_name' => [
                        'label'       => 'Show Student Full Name',
                        'helper'      => 'Displays the student surname and first name in prominent uppercase text.',
                    ],
                    'rc_show_admission_number' => [
                        'label'       => 'Show Admission Number',
                        'helper'      => 'Displays the unique institutional registration number.',
                    ],
                    'rc_show_class' => [
                        'label'       => 'Show Class Name',
                        'helper'      => 'Displays the student classroom level (e.g. SS 2, Primary 5).',
                    ],
                    'rc_show_class_arm' => [
                        'label'       => 'Show Class Arm / Section',
                        'helper'      => 'Displays the assigned arm (e.g. Gold, A, Diamond) when enabled.',
                    ],
                    'rc_show_department' => [
                        'label'       => 'Show Department',
                        'helper'      => 'Displays the academic track (e.g. Science, Arts, Commercial) for senior classes.',
                    ],
                    'rc_show_class_size' => [
                        'label'       => 'Show Number in Class / Set',
                        'helper'      => 'Displays total cohort enrollment count for this classroom level.',
                    ],
                    'rc_show_arm_dept_population' => [
                        'label'       => 'Show Arm / Department Population',
                        'helper'      => 'Displays specific student population count for the arm or department.',
                    ],
                    'rc_show_gender' => [
                        'label'       => 'Show Gender',
                        'helper'      => 'Displays student biological gender (Male/Female).',
                    ],
                    'rc_show_dob' => [
                        'label'       => 'Show Date of Birth',
                        'helper'      => 'Displays student date of birth.',
                    ],
                    'rc_show_attendance' => [
                        'label'       => 'Show Attendance Record',
                        'helper'      => 'Displays school days opened, days present, days absent, and attendance rate percentage.',
                    ],
                ],
            ],

            'body_scores' => [
                'title'       => 'Academic Assessment Table',
                'description' => 'Choose which columns appear in the central subject performance table.',
                'icon'        => 'heroicon-o-table-cells',
                'fields'      => [
                    'rc_show_score_heads' => [
                        'label'       => 'Show Assessment Breakdown Columns',
                        'helper'      => 'Displays continuous assessment columns (e.g. 1st Test, 2nd Test, Mid-Term, Exam).',
                    ],
                    'rc_show_cumulative_terms' => [
                        'label'       => 'Show Cumulative Terms Breakdown (3rd Term)',
                        'helper'      => 'Displays 1st Term, 2nd Term, and 3rd Raw columns during cumulative 3rd term reporting.',
                    ],
                    'rc_show_subject_total' => [
                        'label'       => 'Show Subject Total Score',
                        'helper'      => 'Displays the composite final score column for each subject.',
                    ],
                    'rc_show_students_examined' => [
                        'label'       => 'Show Number of Students Examined',
                        'helper'      => 'Displays the count of candidates who took assessments for that subject.',
                    ],
                    'rc_show_subject_grade' => [
                        'label'       => 'Show Subject Grade',
                        'helper'      => 'Displays the calculated letter grade (A, B, C, D, E, F) with grade color coding.',
                    ],
                    'rc_show_subject_position' => [
                        'label'       => 'Show Subject Position (Rank)',
                        'helper'      => 'Displays student ranking in each subject (e.g. 1st, 2nd, 3rd).',
                    ],
                    'rc_show_subject_teacher_remark' => [
                        'label'       => 'Show Subject Teacher Remark',
                        'helper'      => 'Displays subject teacher qualitative feedback (e.g. Excellent, Credit, Good).',
                    ],
                ],
            ],

            'summary' => [
                'title'       => 'Terminal Performance Summary',
                'description' => 'Control aggregate terminal statistics, totals, class ranks, and final grade badge.',
                'icon'        => 'heroicon-o-chart-bar',
                'fields'      => [
                    'rc_show_performance_summary' => [
                        'label'       => 'Enable Terminal Performance Summary Card',
                        'helper'      => 'Master toggle for the entire summary section below the subject table.',
                    ],
                    'rc_show_total_obtained' => [
                        'label'       => 'Show Total Marks Obtained / Obtainable',
                        'helper'      => 'Displays the grand total points scored over the maximum possible points.',
                    ],
                    'rc_show_term_percentage' => [
                        'label'       => 'Show Term Percentage Average',
                        'helper'      => 'Displays overall percentage score (e.g. 88.5%).',
                    ],
                    'rc_show_overall_position' => [
                        'label'       => 'Show Overall Position in Class',
                        'helper'      => 'Displays overall class/cohort position (e.g. 1st out of 45).',
                    ],
                    'rc_show_arm_dept_position' => [
                        'label'       => 'Show Arm / Department Position',
                        'helper'      => 'Displays localized rank within student arm or department.',
                    ],
                    'rc_show_final_grade' => [
                        'label'       => 'Show Final Academic Grade & Remark',
                        'helper'      => 'Displays overall academic grade classification (e.g. A — Distinction).',
                    ],
                ],
            ],

            'domains_skills' => [
                'title'       => 'Behavioral & Practical Domains',
                'description' => 'Configure Affective Domain (conduct) and Psychomotor Skills (practical capabilities).',
                'icon'        => 'heroicon-o-sparkles',
                'fields'      => [
                    'rc_show_affective_domain' => [
                        'label'       => 'Show Affective Domain (Character & Conduct)',
                        'helper'      => 'Displays ratings for Punctuality, Neatness, Attentiveness, Honesty, Leadership, etc.',
                    ],
                    'rc_show_psychomotor_skills' => [
                        'label'       => 'Show Psychomotor Skills & Practical Activities',
                        'helper'      => 'Displays ratings for Handwriting, Athletics, Crafts, Verbal Fluency, Labs, etc.',
                    ],
                    'rc_show_domain_scale' => [
                        'label'       => 'Show Domain Rating Scale Legend',
                        'helper'      => 'Displays key explaining rating levels: 5 - Excellent, 4 - Very Good, 3 - Good, etc.',
                    ],
                ],
            ],

            'remarks' => [
                'title'       => 'Teacher & Principal Remarks',
                'description' => 'Configure executive comments, class teacher observations, and next term resumption date.',
                'icon'        => 'heroicon-o-chat-bubble-bottom-center-text',
                'fields'      => [
                    'rc_show_class_teacher_remark' => [
                        'label'       => 'Show Class Teacher Remark Card',
                        'helper'      => 'Displays dedicated comment block with personalized class teacher feedback.',
                    ],
                    'rc_show_principal_remark' => [
                        'label'       => 'Show Principal / Head Teacher Remark Card',
                        'helper'      => 'Displays formal executive endorsement by the school head.',
                    ],
                    'rc_show_next_term_resumption' => [
                        'label'       => 'Show Next Term Resumption Date Banner',
                        'helper'      => 'Displays official school reopening date for the upcoming academic term.',
                    ],
                ],
            ],

            'signatories_footer' => [
                'title'       => 'Signatories, Stamp & Footer',
                'description' => 'Configure official signature lines, school seal, and security verification footer.',
                'icon'        => 'heroicon-o-pencil-square',
                'fields'      => [
                    'rc_show_teacher_signature' => [
                        'label'       => 'Show Class Teacher Signature Line',
                        'helper'      => 'Displays signature sign-off line for the class teacher.',
                    ],
                    'rc_show_principal_signature' => [
                        'label'       => 'Show Principal Signature & Date Line',
                        'helper'      => 'Displays executive signature and validation date line.',
                    ],
                    'rc_show_school_stamp' => [
                        'label'       => 'Show Official School Seal / Stamp Box',
                        'helper'      => 'Displays designated border box for physical or digital school stamp.',
                    ],
                    'rc_show_footer_note' => [
                        'label'       => 'Show Computer-Generated Security Footer',
                        'helper'      => 'Displays bottom timestamp verification note with generation date and time.',
                    ],
                ],
            ],

            'layout_margins' => [
                'title'       => 'Paper Margins & Print Spacing',
                'description' => 'Adjust margin width around all sides of the paper so results have generous breathing room.',
                'icon'        => 'heroicon-o-arrows-pointing-out',
                'fields'      => [
                    'rc_page_margin_preset' => [
                        'label'       => 'Paper Margin Preset',
                        'helper'      => 'Choose Standard (18mm, recommended) or Spacious (22mm) for generous margins.',
                    ],
                ],
            ],
        ];
    }
}
