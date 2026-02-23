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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            
            $table->string('full_name');
            $table->string('registration_slug')->unique()->nullable();
            $table->string('registration_token', 64)->nullable(); // SHA-256 produces 64-char hex string
            $table->timestamp('registration_expires_at')->nullable();
            
            $table->enum('status', ['pending', 'active'])
                  ->default('pending');
            
            $table->timestamps();
            
            // Indexes for efficient lookups
            $table->index('registration_slug');
            $table->index(['registration_token', 'registration_expires_at']); // Composite index for validation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
