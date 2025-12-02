<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            // Rename temporary columns to final names
            $table->renameColumn('temp_teacher_id', 'teacher_id');
            $table->renameColumn('temp_ca_score', 'ca_score');
            $table->renameColumn('temp_exam_score', 'exam_score');
            
            // Add evaluation_setting_id and total_score
            $table->foreignId('evaluation_setting_id')->nullable()->after('term_id')->constrained('evaluation_settings')->nullOnDelete();
            $table->decimal('total_score', 5, 2)->storedAs('ca_score + exam_score')->after('exam_score');
            
            // Drop old assessment_type_id and score columns
            $table->dropForeign(['assessment_type_id']);
            $table->dropColumn(['assessment_type_id', 'score']);
            
            // Add unique constraint to prevent duplicates
            $table->unique(['student_id', 'subject_id', 'term_id', 'academic_session_id'], 'unique_student_subject_term_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('unique_student_subject_term_session');
            
            // Restore old columns
            $table->foreignId('assessment_type_id')->after('subject_id')->constrained()->cascadeOnDelete();
            $table->integer('score')->after('assessment_type_id');
            
            // Drop new columns
            $table->dropColumn(['evaluation_setting_id', 'total_score']);
            
            // Rename back to temporary names
            $table->renameColumn('teacher_id', 'temp_teacher_id');
            $table->renameColumn('ca_score', 'temp_ca_score');
            $table->renameColumn('exam_score', 'temp_exam_score');
        });
    }
};
