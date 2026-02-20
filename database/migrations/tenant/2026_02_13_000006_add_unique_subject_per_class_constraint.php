<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_subject_assignments', function (Blueprint $table) {
            // One teacher per subject per class per session/term
            $table->unique(
                ['subject_id', 'classroom_id', 'session_id', 'term_id'],
                'unique_subject_per_class'
            );
        });
    }

    public function down(): void
    {
        Schema::table('teacher_subject_assignments', function (Blueprint $table) {
            $table->dropUnique('unique_subject_per_class');
        });
    }
};
