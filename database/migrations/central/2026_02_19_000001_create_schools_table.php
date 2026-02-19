<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     */
    public function getConnection(): ?string
    {
        return 'central';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('central')->create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('database_name')->unique();
            $table->string('database_username');
            $table->text('database_password'); // Encrypted via Laravel's Crypt
            $table->enum('status', ['active', 'suspended'])->default('active')->index();
            $table->timestamps();

            // Performance index for status-based lookups
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->dropIfExists('schools');
    }
};
