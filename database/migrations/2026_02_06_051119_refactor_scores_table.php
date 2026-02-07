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
            // Add new columns if they don't exist
            if (!Schema::hasColumn('scores', 'score_header_id')) {
                $table->foreignId('score_header_id')->after('subject_id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('scores', 'session')) {
                $table->string('session')->after('score_header_id');
            }
            if (!Schema::hasColumn('scores', 'term')) {
                $table->integer('term')->after('session');
            }
            if (!Schema::hasColumn('scores', 'value')) {
                $table->decimal('value', 5, 2)->default(0)->after('term');
            }
            
            // Drop old columns if they exist
            if (Schema::hasColumn('scores', 'academic_session_id')) {
                $table->dropForeign(['academic_session_id']);
                $table->dropColumn('academic_session_id');
            }
            if (Schema::hasColumn('scores', 'term_id')) {
                $table->dropForeign(['term_id']);
                $table->dropColumn('term_id');
            }
            if (Schema::hasColumn('scores', 'ca_score')) {
                $table->dropColumn('ca_score');
            }
            if (Schema::hasColumn('scores', 'exam_score')) {
                $table->dropColumn('exam_score');
            }
            
            // Add unique constraint and indexes
            $table->unique(['student_id', 'subject_id', 'score_header_id', 'session', 'term'], 'unique_score');
            $table->index(['session', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            // Drop new columns
            $table->dropUnique('unique_score');
            $table->dropIndex(['session', 'term']);
            $table->dropForeign(['score_header_id']);
            $table->dropColumn(['score_header_id', 'session', 'term', 'value']);
            
            // Restore old columns
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->integer('ca_score')->default(0);
            $table->integer('exam_score')->default(0);
        });
    }
};
