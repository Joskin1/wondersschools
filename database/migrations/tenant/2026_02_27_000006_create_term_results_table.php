<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->unsignedTinyInteger('subjects_count')->default(0);
            $table->decimal('grand_total', 8, 2)->default(0);
            $table->decimal('average', 6, 2)->default(0);
            $table->string('grade', 2)->default('');
            $table->string('remark', 50)->default('');
            $table->unsignedSmallInteger('overall_position')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->timestamps();

            $table->unique(
                ['student_id', 'session_id', 'term_id'],
                'unique_term_result'
            );

            $table->index(['classroom_id', 'session_id', 'term_id'], 'term_results_class_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_results');
    }
};
