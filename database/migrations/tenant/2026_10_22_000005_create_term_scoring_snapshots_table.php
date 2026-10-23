<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_scoring_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->string('mode', 20)->default('midterm'); // 'legacy' or 'midterm'
            $table->unsignedTinyInteger('midterm_weight')->default(0);
            $table->unsignedSmallInteger('raw_max')->default(0);
            $table->string('calculation_method', 50)->default('scale_to_weight');
            $table->string('absent_policy', 30)->default('count_as_zero');
            $table->unsignedTinyInteger('result_decimal_places')->default(2);
            $table->string('result_rounding_rule', 30)->default('half_up');
            $table->json('midterm_heads'); // Array of {id, name, max_score, effective_max}
            $table->json('exam_heads');    // Array of {id, name, max_score, effective_max}
            $table->foreignId('snapshotted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('snapshotted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['classroom_id', 'session_id', 'term_id'], 'unique_term_scoring_snapshot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_scoring_snapshots');
    }
};
