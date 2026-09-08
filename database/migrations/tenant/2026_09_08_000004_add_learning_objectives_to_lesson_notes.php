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
        Schema::table('lesson_notes', function (Blueprint $table) {
            $table->json('learning_objectives')->nullable()->after('week_number');
        });

        Schema::table('lesson_note_versions', function (Blueprint $table) {
            $table->json('learning_objectives')->nullable()->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_note_versions', function (Blueprint $table) {
            $table->dropColumn('learning_objectives');
        });

        Schema::table('lesson_notes', function (Blueprint $table) {
            $table->dropColumn('learning_objectives');
        });
    }
};
