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
        Schema::create('lesson_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->unsignedTinyInteger('week_number'); // 1-12
            $table->unsignedBigInteger('latest_version_id')->nullable(); // Foreign key added in separate migration
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            // Critical indexes for 5M+ concurrent users
            // Admin filtering: "Show me all lesson notes for JSS 1A, Week 3"
            $table->index(['classroom_id', 'subject_id'], 'classroom_subject_idx');
            
            // Session/term/week filtering: "Show me all notes for 2024-2025, First Term, Week 3"
            $table->index(['session_id', 'term_id', 'week_number'], 'session_term_week_idx');
            
            // Teacher queries: "Show me my lesson notes"
            $table->index('teacher_id');
            
            // Status filtering: "Show me all pending notes"
            $table->index('status');
            
            // Prevent duplicate submissions
            $table->unique(['teacher_id', 'subject_id', 'classroom_id', 'session_id', 'term_id', 'week_number'], 'unique_lesson_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_notes');
    }
};
