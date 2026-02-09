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
        Schema::create('term_migrations_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('from_session_id')
                ->nullable()
                ->constrained('academic_sessions')
                ->nullOnDelete();
            $table->foreignId('from_term_id')
                ->nullable()
                ->constrained('terms')
                ->nullOnDelete();
            $table->foreignId('to_session_id')
                ->constrained('academic_sessions')
                ->cascadeOnDelete();
            $table->foreignId('to_term_id')
                ->constrained('terms')
                ->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index for querying migration history
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_migrations_log');
    }
};
