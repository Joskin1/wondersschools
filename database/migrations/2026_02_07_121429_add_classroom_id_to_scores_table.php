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
        Schema::table('scores', function (Blueprint $table) {
            $table->foreignId('classroom_id')
                ->after('subject_id')
                ->constrained('classrooms')
                ->cascadeOnDelete();
            
            // Add index for performance
            $table->index(['classroom_id', 'session', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropIndex(['classroom_id', 'session', 'term']);
            $table->dropForeign(['classroom_id']);
            $table->dropColumn('classroom_id');
        });
    }
};
