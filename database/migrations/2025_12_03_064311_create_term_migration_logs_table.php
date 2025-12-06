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
        Schema::create('term_migration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_term_id')->constrained('terms');
            $table->foreignId('to_term_id')->constrained('terms');
            $table->foreignId('from_session_id')->constrained('academic_sessions');
            $table->foreignId('to_session_id')->constrained('academic_sessions');
            $table->foreignId('user_id')->constrained('users');
            $table->integer('students_promoted')->default(0);
            $table->integer('students_graduated')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_migration_logs');
    }
};
