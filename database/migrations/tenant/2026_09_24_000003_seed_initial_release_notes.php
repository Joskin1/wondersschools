<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $releases = [
            [
                'version' => 'v1.3.0',
                'title' => 'Standardized Lesson Note & Lesson Plan Online Editor',
                'category' => 'workflow_change',
                'summary' => 'Replaced arbitrary file uploads with a standardized online rich-text builder, automated formatting enforcement, auto-draft recovery, and synchronized paired submissions for review.',
                'changes' => json_encode([
                    'Replaced PDF/document file uploads with structured online rich-text builder for both lesson notes and lesson plans.',
                    'Added automatic browser crash/reload auto-draft recovery so unsaved work is never lost.',
                    'Enforced strict formatting rules: Topics are automatically formatted in UPPERCASE, and Subtopics in Title Case.',
                    'Synchronized Pair Submission: Submitting a lesson plan automatically couples and submits the corresponding lesson note to the Admin queue.',
                    'Admin Review Interface: Inline document viewer with instant Approval/Rejection feedback notes and branded PDF export.'
                ]),
                'procedure_guide' => "1. Teachers must now author Lesson Notes and Lesson Plans directly inside the portal using the 'Write Online' rich text editor.\n2. Lesson Notes must be created and saved first before authoring the corresponding Lesson Plan for the same Subject, Class, and Week.\n3. From the Lesson Plan edit page, clicking 'Submit for Review' submits both the Lesson Note and Lesson Plan together to the administration.\n4. Admins review both submissions simultaneously in the Admin Review Queue with instant feedback and branded PDF generation.",
                'published_at' => $now->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
            [
                'version' => 'v1.2.0',
                'title' => 'Dynamic Score Structure & Multi-Head Assessment Configuration',
                'category' => 'feature',
                'summary' => 'Flexible score head management allowing administrators to define custom assessment weighting (e.g., 1st CA, 2nd CA, Midterm, Exams) per academic session and class.',
                'changes' => json_encode([
                    'Configurable Score Heads with customizable maximum score limits and active states.',
                    'Class Score Structure builder with 100% total weight validation per class and academic session.',
                    'Administrative Structure Lock/Unlock protection to ensure assessment integrity during active grading.',
                    'Dynamic teacher score entry grid that adapts in real-time to the configured score heads and weight distribution.'
                ]),
                'procedure_guide' => "1. Administrators configure assessment components under Academic Management -> Score Heads (e.g. Test 1 = 15 marks, Test 2 = 15 marks, Exam = 70 marks).\n2. Navigate to Academic Management -> Score Structure to assign and lock the score head breakdown for each class and term.\n3. Subject teachers can then enter student scores under Results -> Enter Scores based on the configured structure.",
                'published_at' => $now->subDays(7)->toDateTimeString(),
                'created_at' => $now->subDays(7)->toDateTimeString(),
                'updated_at' => $now->subDays(7)->toDateTimeString(),
            ],
            [
                'version' => 'v1.1.0',
                'title' => 'Automated Class Result Compilation & Branded Student Report Cards',
                'category' => 'feature',
                'summary' => 'End-to-end automated grading and result processing pipeline featuring automatic student position calculation, subject totals, class averages, and branded report card PDF exports.',
                'changes' => json_encode([
                    'Automated computation of Subject Totals and letter grades as teachers submit assessment scores.',
                    'Process Class Results workflow with automatic student class ranking, GPA/averages, and pass/fail statistics.',
                    'High-resolution branded PDF Report Card generator featuring school crest, grading legend, and teacher/principal comments.',
                    'Instant Student & Parent Portal access to view and download finalized term report cards.'
                ]),
                'procedure_guide' => "1. Ensure all subject teachers have published student scores for the term.\n2. Go to Results -> Process Class Results, select the Academic Session, Term, and Class.\n3. Click 'Process Class Results' to calculate student ranks and term aggregates.\n4. Review and finalize results to make them immediately visible on the Student Portal and available for bulk PDF printing.",
                'published_at' => $now->subDays(14)->toDateTimeString(),
                'created_at' => $now->subDays(14)->toDateTimeString(),
                'updated_at' => $now->subDays(14)->toDateTimeString(),
            ],
        ];

        foreach ($releases as $release) {
            DB::table('release_notes')->updateOrInsert(
                ['version' => $release['version']],
                $release
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('release_notes')->whereIn('version', ['v1.3.0', 'v1.2.0', 'v1.1.0'])->delete();
    }
};
