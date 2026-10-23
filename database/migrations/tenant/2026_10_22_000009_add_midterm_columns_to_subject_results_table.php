<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_results', function (Blueprint $table) {
            $table->decimal('midterm_score', 5, 2)->nullable()->after('term_id');
            $table->decimal('exam_score', 5, 2)->nullable()->after('midterm_score');
            $table->string('midterm_status', 20)->default('not_applicable')->after('exam_score');
            $table->boolean('has_missing_midterm')->default(false)->after('midterm_status');
            $table->boolean('has_error')->default(false)->after('has_missing_midterm');
            $table->text('error_message')->nullable()->after('has_error');
        });
    }

    public function down(): void
    {
        Schema::table('subject_results', function (Blueprint $table) {
            $table->dropColumn([
                'midterm_score',
                'exam_score',
                'midterm_status',
                'has_missing_midterm',
                'has_error',
                'error_message',
            ]);
        });
    }
};
