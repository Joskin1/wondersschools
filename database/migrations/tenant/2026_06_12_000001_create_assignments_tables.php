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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->unsignedInteger('week_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Prevent duplicate assignments for same subject, class, and week schedule
            $table->unique(
                ['subject_id', 'classroom_id', 'session_id', 'term_id', 'week_number'],
                'assignments_schedule_unique'
            );
        });

        Schema::create('assignment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->text('question_text');
            $table->json('options'); // Array of choices e.g. ["A", "B", "C", "D"]
            $table->string('correct_option'); // Must match one of the options text or options index
            $table->unsignedInteger('points')->default(1);
            $table->timestamps();
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->json('answers'); // JSON mapping: question_id => selected_option
            $table->unsignedInteger('score');
            $table->unsignedInteger('total_points');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Prevent duplicate submissions per student per assignment
            $table->unique(['student_id', 'assignment_id'], 'student_assignment_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignment_questions');
        Schema::dropIfExists('assignments');
    }
};
