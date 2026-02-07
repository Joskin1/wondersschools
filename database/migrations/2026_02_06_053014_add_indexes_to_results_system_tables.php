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
        // Results table indexes
        if (!Schema::hasIndex('results', 'idx_results_student_session_term')) {
            Schema::table('results', function (Blueprint $table) {
                $table->index(['student_id', 'session', 'term'], 'idx_results_student_session_term');
                $table->index(['classroom_id', 'session', 'term'], 'idx_results_classroom_session_term');
                $table->index('cache_key', 'idx_results_cache_key');
                $table->index('position', 'idx_results_position');
                $table->index('generated_at', 'idx_results_generated_at');
            });
        }

        // Scores table indexes
        if (!Schema::hasIndex('scores', 'idx_scores_student_subject_session_term')) {
            Schema::table('scores', function (Blueprint $table) {
                $table->index(['student_id', 'subject_id', 'session', 'term'], 'idx_scores_student_subject_session_term');
                $table->index(['score_header_id', 'session', 'term'], 'idx_scores_header_session_term');
            });
        }

        // Result comments table indexes
        Schema::table('result_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('result_comments', 'result_id')) {
                return; // Skip if column doesn't exist
            }
            $table->index('result_id', 'idx_result_comments_result');
            if (Schema::hasColumn('result_comments', 'comment_type')) {
                $table->index('comment_type', 'idx_result_comments_type');
            }
        });

        // Gradings table indexes (if not already present)
        if (!Schema::hasIndex('gradings', 'idx_gradings_subject_session')) {
            Schema::table('gradings', function (Blueprint $table) {
                $table->index(['subject_id', 'session'], 'idx_gradings_subject_session');
                $table->index(['lower_bound', 'upper_bound'], 'idx_gradings_bounds');
            });
        }

        // Score headers table indexes (if not already present)
        if (!Schema::hasIndex('score_headers', 'idx_score_headers_classroom_session_term')) {
            Schema::table('score_headers', function (Blueprint $table) {
                $table->index(['school_class_id', 'session', 'term'], 'idx_score_headers_classroom_session_term');
                $table->index('display_order', 'idx_score_headers_display_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex('idx_results_student_session_term');
            $table->dropIndex('idx_results_classroom_session_term');
            $table->dropIndex('idx_results_cache_key');
            $table->dropIndex('idx_results_position');
            $table->dropIndex('idx_results_generated_at');
        });

        Schema::table('scores', function (Blueprint $table) {
            $table->dropIndex('idx_scores_student_subject_session_term');
            $table->dropIndex('idx_scores_header_session_term');
        });

        Schema::table('result_comments', function (Blueprint $table) {
            if (Schema::hasIndex('result_comments', 'idx_result_comments_result')) {
                $table->dropIndex('idx_result_comments_result');
            }
            if (Schema::hasIndex('result_comments', 'idx_result_comments_type')) {
                $table->dropIndex('idx_result_comments_type');
            }
        });

        if (Schema::hasIndex('gradings', 'idx_gradings_subject_session')) {
            Schema::table('gradings', function (Blueprint $table) {
                $table->dropIndex('idx_gradings_subject_session');
                $table->dropIndex('idx_gradings_bounds');
            });
        }

        if (Schema::hasIndex('score_headers', 'idx_score_headers_classroom_session_term')) {
            Schema::table('score_headers', function (Blueprint $table) {
                $table->dropIndex('idx_score_headers_classroom_session_term');
                $table->dropIndex('idx_score_headers_display_order');
            });
        }
    }
};
