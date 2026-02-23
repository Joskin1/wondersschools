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
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            $table->foreignId('classroom_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            $table->foreignId('session_id')
                  ->constrained('academic_sessions')
                  ->cascadeOnDelete();
            
            $table->timestamps();
            
            // Ensure one class per student per session
            $table->unique(['student_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
