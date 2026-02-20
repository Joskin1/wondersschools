<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->string('id')->primary(); // Stancl Tenant primary key
            
            // Custom Columns
            $table->string('name');
            $table->string('database_name')->unique();
            $table->string('database_username');
            $table->text('database_password'); // Encrypted via Laravel's Crypt
            $table->enum('status', ['active', 'suspended'])->default('active')->index();
            
            // Stancl specific data column for extra attributes
            $table->json('data')->nullable();
            
            $table->timestamps();

            // Performance index for status-based lookups
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
