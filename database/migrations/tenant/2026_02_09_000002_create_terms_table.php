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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('academic_sessions')
                ->cascadeOnDelete();
            $table->enum('name', ['First Term', 'Second Term', 'Third Term']);
            $table->tinyInteger('order'); // 1, 2, or 3
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // Ensure unique term order within a session
            $table->unique(['session_id', 'order']);
            
            // Composite index for fast queries by session and term
            $table->index(['session_id', 'id']);
            
            // Index for fast active term queries
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
