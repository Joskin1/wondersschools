<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_results', function (Blueprint $table) {
            $table->unsignedSmallInteger('times_present')->nullable()->after('overall_position');
            $table->text('class_teacher_comment')->nullable()->after('times_present');
        });
    }

    public function down(): void
    {
        Schema::table('term_results', function (Blueprint $table) {
            $table->dropColumn(['times_present', 'class_teacher_comment']);
        });
    }
};
