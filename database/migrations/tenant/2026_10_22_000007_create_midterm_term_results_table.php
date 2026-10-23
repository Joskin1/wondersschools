<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midterm_term_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->unsignedTinyInteger('subjects_count')->default(0);
            $table->decimal('total_obtainable', 8, 2)->default(0);
            $table->decimal('total_obtained', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('grade', 10)->default('');
            $table->string('remark', 50)->default('');
            $table->text('teacher_comment')->nullable();
            $table->text('head_teacher_comment')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'session_id', 'term_id'], 'unique_midterm_term_result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midterm_term_results');
    }
};
