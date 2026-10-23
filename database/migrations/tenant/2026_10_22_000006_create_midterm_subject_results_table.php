<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midterm_subject_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->decimal('raw_score', 6, 2)->default(0);
            $table->unsignedSmallInteger('student_raw_max')->default(0);
            $table->unsignedTinyInteger('midterm_weight')->default(0);
            $table->decimal('scaled_score', 5, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade', 10)->default('');
            $table->string('remark', 50)->default('');
            $table->string('status', 20)->default('complete'); // 'complete', 'absent', 'missing'
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'session_id', 'term_id'], 'unique_midterm_subject_result');
            $table->index(['classroom_id', 'subject_id', 'session_id', 'term_id'], 'midterm_subj_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midterm_subject_results');
    }
};
