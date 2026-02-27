<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->decimal('total', 6, 2)->default(0);
            $table->string('grade', 2)->default('');
            $table->string('remark', 50)->default('');
            $table->unsignedSmallInteger('position')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'subject_id', 'session_id', 'term_id'],
                'unique_subject_result'
            );

            $table->index(['classroom_id', 'subject_id', 'session_id', 'term_id'], 'subject_results_class_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_results');
    }
};
