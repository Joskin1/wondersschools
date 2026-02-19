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
        Schema::connection('central')->create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Performance index for domain lookups (most critical query)
            $table->index(['domain']);
            // Index for school-based domain lookups
            $table->index(['school_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->dropIfExists('domains');
    }
};
