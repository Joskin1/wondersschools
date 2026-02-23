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
        Schema::table('class_teacher_assignments', function (Blueprint $table) {
            // A teacher can only be class teacher for one class per session
            $table->unique(['teacher_id', 'session_id'], 'unique_teacher_per_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_teacher_assignments', function (Blueprint $table) {
            $table->dropUnique('unique_teacher_per_session');
        });
    }
};
