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
        Schema::create('teacher_subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->timestamps();

            // Composite index for fast permission checks
            $table->index(['teacher_id', 'subject_id', 'classroom_id'], 'teacher_subject_classroom_idx');
            
            // Index for filtering by session/term
            $table->index(['session_id', 'term_id'], 'session_term_idx');
            
            // Prevent duplicate assignments
            $table->unique(['teacher_id', 'subject_id', 'classroom_id', 'session_id', 'term_id'], 'unique_assignment');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_subject_assignments');
    }
};
