<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_results', function (Blueprint $table) {
            $table->text('principal_comment')->nullable()->after('class_teacher_comment');
        });
    }

    public function down(): void
    {
        Schema::table('term_results', function (Blueprint $table) {
            $table->dropColumn('principal_comment');
        });
    }
};
