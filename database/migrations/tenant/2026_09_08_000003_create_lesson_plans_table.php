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
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->unsignedTinyInteger('week_number');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->string('title')->nullable();
            $table->string('time')->nullable();
            $table->string('section')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->text('key_vocabulary')->nullable();
            $table->text('prior_knowledge')->nullable();
            $table->longText('content')->nullable();
            $table->json('presentation_steps')->nullable();
            $table->text('strategies_activities')->nullable();
            $table->json('evaluation_questions')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('assignment')->nullable();
            $table->text('admin_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Critical indexes
            $table->index(['classroom_id', 'subject_id'], 'lp_classroom_subject_idx');
            $table->index(['session_id', 'term_id', 'week_number'], 'lp_session_term_week_idx');
            $table->index('teacher_id', 'lp_teacher_idx');
            $table->index('status', 'lp_status_idx');

            $table->unique(['teacher_id', 'subject_id', 'classroom_id', 'session_id', 'term_id', 'week_number'], 'unique_lesson_plan');
        });

        Schema::create('lesson_plan_reference_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_id')->constrained('lesson_plans')->onDelete('cascade');
            $table->foreignId('reference_material_id')->constrained('reference_materials')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['lesson_plan_id', 'reference_material_id'], 'lp_ref_mat_unique');
        });

        Schema::create('lesson_plan_instructional_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_id')->constrained('lesson_plans')->onDelete('cascade');
            $table->foreignId('instructional_material_id')->constrained('instructional_materials')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['lesson_plan_id', 'instructional_material_id'], 'lp_inst_mat_unique');
        });

        Schema::create('lesson_plan_teaching_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_id')->constrained('lesson_plans')->onDelete('cascade');
            $table->foreignId('teaching_method_id')->constrained('teaching_methods')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['lesson_plan_id', 'teaching_method_id'], 'lp_teach_method_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_teaching_methods');
        Schema::dropIfExists('lesson_plan_instructional_materials');
        Schema::dropIfExists('lesson_plan_reference_materials');
        Schema::dropIfExists('lesson_plans');
    }
};
