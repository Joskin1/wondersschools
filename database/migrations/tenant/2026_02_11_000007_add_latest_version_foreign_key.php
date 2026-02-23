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
            $table->foreign('latest_version_id')
                ->references('id')
                ->on('lesson_note_versions')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_notes', function (Blueprint $table) {
            $table->dropForeign(['latest_version_id']);
        });
    }
};
