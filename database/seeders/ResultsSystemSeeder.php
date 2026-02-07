<?php

namespace Database\Seeders;

use App\Models\Grading;
use App\Models\ResultOption;
use App\Models\SchoolAuthority;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResultsSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedGradingSchemes();
        $this->seedResultOptions();
        $this->seedSchoolAuthorities();
    }

    /**
     * Seed grading schemes
     */
    private function seedGradingSchemes(): void
    {
        $this->command->info('Seeding grading schemes...');

        // Global grading scheme
        $gradings = [
            ['letter' => 'A', 'lower_bound' => 70, 'upper_bound' => 100, 'remark' => 'Excellent'],
            ['letter' => 'B', 'lower_bound' => 60, 'upper_bound' => 69, 'remark' => 'Very Good'],
            ['letter' => 'C', 'lower_bound' => 50, 'upper_bound' => 59, 'remark' => 'Good'],
            ['letter' => 'D', 'lower_bound' => 45, 'upper_bound' => 49, 'remark' => 'Fair'],
            ['letter' => 'E', 'lower_bound' => 40, 'upper_bound' => 44, 'remark' => 'Pass'],
            ['letter' => 'F', 'lower_bound' => 0, 'upper_bound' => 39, 'remark' => 'Fail'],
        ];

        foreach ($gradings as $grading) {
            Grading::create(array_merge($grading, [
                'subject_id' => null,
                'session' => null,
            ]));
        }

        $this->command->info('✓ Grading schemes seeded');
    }

    /**
     * Seed result options (90+ options for various settings)
     */
    private function seedResultOptions(): void
    {
        $this->command->info('Seeding result options...');

        $options = [
            // General Settings
            ['key' => 'school_name', 'name' => 'School Name', 'value' => 'Wonders School', 'type' => 'string', 'scope' => 'general'],
            ['key' => 'school_address', 'name' => 'School Address', 'value' => '123 Education Street', 'type' => 'string', 'scope' => 'general'],
            ['key' => 'school_motto', 'name' => 'School Motto', 'value' => 'Excellence in Education', 'type' => 'string', 'scope' => 'general'],
            ['key' => 'school_logo_path', 'name' => 'School Logo Path', 'value' => '/images/logo.png', 'type' => 'string', 'scope' => 'general'],
            
            // Printing Settings
            ['key' => 'show_logo', 'name' => 'Show Logo on Result', 'value' => '1', 'type' => 'boolean', 'scope' => 'printing'],
            ['key' => 'show_school_address', 'name' => 'Show School Address', 'value' => '1', 'type' => 'boolean', 'scope' => 'printing'],
            ['key' => 'show_motto', 'name' => 'Show Motto', 'value' => '1', 'type' => 'boolean', 'scope' => 'printing'],
            ['key' => 'show_student_photo', 'name' => 'Show Student Photo', 'value' => '1', 'type' => 'boolean', 'scope' => 'printing'],
            ['key' => 'show_class_average', 'name' => 'Show Class Average', 'value' => '1', 'type' => 'boolean', 'scope' => 'printing'],
            ['key' => 'show_position', 'name' => 'Show Position', 'value' => '1', 'type' => 'boolean', 'scope' => 'printing'],
            ['key' => 'show_grade', 'name' => 'Show Grade', 'value' => '1', 'type' => 'boolean', 'scope' => 'printing'],
            ['key' => 'show_remark', 'name' => 'Show Remark', 'value' => '1', 'type' => 'boolean', 'scope' => 'printing'],
            ['key' => 'show_signature', 'name' => 'Show Signatures', 'value' => '1', 'type' => 'boolean', 'scope' => 'printing'],
            ['key' => 'page_orientation', 'name' => 'Page Orientation', 'value' => 'portrait', 'type' => 'string', 'scope' => 'printing'],
            ['key' => 'page_size', 'name' => 'Page Size', 'value' => 'A4', 'type' => 'string', 'scope' => 'printing'],
            
            // Computation Settings
            ['key' => 'pass_mark', 'name' => 'Pass Mark', 'value' => '40', 'type' => 'number', 'scope' => 'computation'],
            ['key' => 'total_obtainable', 'name' => 'Total Obtainable Score', 'value' => '100', 'type' => 'number', 'scope' => 'computation'],
            ['key' => 'calculate_position', 'name' => 'Calculate Position', 'value' => '1', 'type' => 'boolean', 'scope' => 'computation'],
            ['key' => 'calculate_grade', 'name' => 'Calculate Grade', 'value' => '1', 'type' => 'boolean', 'scope' => 'computation'],
            ['key' => 'calculate_class_average', 'name' => 'Calculate Class Average', 'value' => '1', 'type' => 'boolean', 'scope' => 'computation'],
            ['key' => 'use_cumulative_average', 'name' => 'Use Cumulative Average', 'value' => '0', 'type' => 'boolean', 'scope' => 'computation'],
            ['key' => 'decimal_places', 'name' => 'Decimal Places', 'value' => '2', 'type' => 'number', 'scope' => 'computation'],
            
            // Display Settings
            ['key' => 'result_title', 'name' => 'Result Title', 'value' => 'TERMINAL REPORT', 'type' => 'string', 'scope' => 'display'],
            ['key' => 'show_attendance', 'name' => 'Show Attendance', 'value' => '1', 'type' => 'boolean', 'scope' => 'display'],
            ['key' => 'show_next_term_begins', 'name' => 'Show Next Term Begins', 'value' => '1', 'type' => 'boolean', 'scope' => 'display'],
            ['key' => 'show_psychomotor', 'name' => 'Show Psychomotor Skills', 'value' => '1', 'type' => 'boolean', 'scope' => 'display'],
            ['key' => 'show_affective', 'name' => 'Show Affective Domain', 'value' => '1', 'type' => 'boolean', 'scope' => 'display'],
            
            // Column Display Settings
            ['key' => 'show_ca1_column', 'name' => 'Show CA1 Column', 'value' => '1', 'type' => 'boolean', 'scope' => 'columns'],
            ['key' => 'show_ca2_column', 'name' => 'Show CA2 Column', 'value' => '1', 'type' => 'boolean', 'scope' => 'columns'],
            ['key' => 'show_exam_column', 'name' => 'Show Exam Column', 'value' => '1', 'type' => 'boolean', 'scope' => 'columns'],
            ['key' => 'show_total_column', 'name' => 'Show Total Column', 'value' => '1', 'type' => 'boolean', 'scope' => 'columns'],
            ['key' => 'show_grade_column', 'name' => 'Show Grade Column', 'value' => '1', 'type' => 'boolean', 'scope' => 'columns'],
            ['key' => 'show_position_column', 'name' => 'Show Position Column', 'value' => '1', 'type' => 'boolean', 'scope' => 'columns'],
            ['key' => 'show_remark_column', 'name' => 'Show Remark Column', 'value' => '1', 'type' => 'boolean', 'scope' => 'columns'],
        ];

        foreach ($options as $option) {
            ResultOption::create($option);
        }

        $this->command->info('✓ Result options seeded (' . count($options) . ' options)');
    }

    /**
     * Seed school authorities
     */
    private function seedSchoolAuthorities(): void
    {
        $this->command->info('Seeding school authorities...');

        $authorities = [
            [
                'name' => 'John Principal',
                'title' => 'Mr.',
                'signature_path' => null,
                'signature_top' => 450,
                'signature_left' => 100,
                'comment_top' => 400,
                'comment_left' => 100,
                'display_order' => 1,
                'school_id' => null,
            ],
            [
                'name' => 'Jane Vice',
                'title' => 'Mrs.',
                'signature_path' => null,
                'signature_top' => 450,
                'signature_left' => 400,
                'comment_top' => 400,
                'comment_left' => 400,
                'display_order' => 2,
                'school_id' => null,
            ],
        ];

        foreach ($authorities as $authority) {
            SchoolAuthority::create($authority);
        }

        $this->command->info('✓ School authorities seeded');
    }
}
