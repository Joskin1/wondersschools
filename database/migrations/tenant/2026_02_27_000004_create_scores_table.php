<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('score_head_id')->constrained('score_heads')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->timestamps();

            // Prevent duplicate entries per student/subject/score-head/period
            $table->unique(
                ['student_id', 'subject_id', 'score_head_id', 'session_id', 'term_id'],
                'unique_student_score'
            );

            // Query-optimisation indexes
            $table->index(
                ['student_id', 'subject_id', 'term_id', 'session_id'],
                'scores_query_idx'
            );
            $table->index(['classroom_id', 'subject_id'], 'scores_class_subject_idx');
            $table->index('teacher_id', 'scores_teacher_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
