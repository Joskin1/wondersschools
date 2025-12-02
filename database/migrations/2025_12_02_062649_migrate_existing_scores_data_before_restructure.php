<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration transforms existing scores from the old assessment_types system
     * to the new CA/Exam structure before the table is restructured.
     */
    public function up(): void
    {
        // Add temporary columns to store CA and Exam scores
        Schema::table('scores', function (Blueprint $table) {
            $table->foreignId('temp_teacher_id')->nullable()->after('student_id');
            $table->decimal('temp_ca_score', 5, 2)->default(0)->after('score');
            $table->decimal('temp_exam_score', 5, 2)->default(0)->after('temp_ca_score');
        });

        // Get all unique student/subject/term/session combinations
        $scoreGroups = DB::table('scores')
            ->select('student_id', 'subject_id', 'academic_session_id', 'term_id')
            ->distinct()
            ->get();

        foreach ($scoreGroups as $group) {
            // Get all scores for this combination
            $scores = DB::table('scores')
                ->where('student_id', $group->student_id)
                ->where('subject_id', $group->subject_id)
                ->where('academic_session_id', $group->academic_session_id)
                ->where('term_id', $group->term_id)
                ->get();

            $caScore = 0;
            $examScore = 0;

            // Aggregate scores based on assessment type names
            foreach ($scores as $score) {
                $assessmentType = DB::table('assessment_types')
                    ->where('id', $score->assessment_type_id)
                    ->first();

                if ($assessmentType) {
                    // If assessment type contains "exam" (case insensitive), treat as exam
                    if (stripos($assessmentType->name, 'exam') !== false) {
                        $examScore += $score->score;
                    } else {
                        // Otherwise treat as CA
                        $caScore += $score->score;
                    }
                }
            }

            // Get teacher from student's classroom (if available)
            $student = DB::table('students')->where('id', $group->student_id)->first();
            $teacherId = null;
            
            if ($student && $student->classroom_id) {
                $classroom = DB::table('classrooms')->where('id', $student->classroom_id)->first();
                if ($classroom && $classroom->staff_id) {
                    $teacherId = $classroom->staff_id;
                }
            }

            // Update the first score record with aggregated values
            $firstScore = $scores->first();
            if ($firstScore) {
                DB::table('scores')
                    ->where('id', $firstScore->id)
                    ->update([
                        'temp_teacher_id' => $teacherId,
                        'temp_ca_score' => $caScore,
                        'temp_exam_score' => $examScore,
                    ]);

                // Delete other scores for this combination (duplicates)
                DB::table('scores')
                    ->where('student_id', $group->student_id)
                    ->where('subject_id', $group->subject_id)
                    ->where('academic_session_id', $group->academic_session_id)
                    ->where('term_id', $group->term_id)
                    ->where('id', '!=', $firstScore->id)
                    ->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop temporary columns
        Schema::table('scores', function (Blueprint $table) {
            $table->dropColumn(['temp_teacher_id', 'temp_ca_score', 'temp_exam_score']);
        });
    }
};
