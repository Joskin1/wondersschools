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
        Schema::create('submission_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->unsignedTinyInteger('week_number'); // 1-12
            $table->timestamp('opens_at');
            $table->timestamp('closes_at');
            $table->boolean('is_open')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Unique constraint - one window per session/term/week
            $table->unique(['session_id', 'term_id', 'week_number'], 'unique_window');
            
            // Index for fast "is window open?" queries (heavily cached)
            $table->index('is_open');
            $table->index(['opens_at', 'closes_at'], 'window_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_windows');
    }
};
